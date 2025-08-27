import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api-service';
import { DatePipe } from '@angular/common';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogComponent } from '../confirm-dialog/confirm-dialog';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
  selector: 'app-amc-update',
  standalone: false,
  templateUrl: './amc-update.html',
  styleUrl: './amc-update.scss',
  providers: [DatePipe] // ✅ Provide DatePipe
})
export class AmcUpdateComponent implements OnInit {
  amcForm!: FormGroup;
  submitted = false;
  enquiryId: string | null = null;
  isEditMode = false; // Set to true if updating
  yearsList: number[] = Array.from({ length: 10 }, (_, i) => i + 1);
  constructor(private fb: FormBuilder, private router: Router, private apiService: ApiService,
    private datePipe: DatePipe, private dialog: MatDialog, private snackBar: MatSnackBar) {
    const nav = this.router.getCurrentNavigation();
    const state = nav?.extras?.state as { enquiryId?: string, editMode?: boolean };
    this.enquiryId = state?.enquiryId || null;
    this.isEditMode = state?.editMode || false;
    console.log('this.enquiryId', this.enquiryId);
    console.log('Edit Mode:', this.isEditMode);
  }

  ngOnInit(): void {
    this.amcForm = this.fb.group({
      client_name: ['', Validators.required],
      contact_person: ['', Validators.required],
      contact_number: ['', Validators.required],
      delivered_date: ['', Validators.required],
      no_of_years: ['', Validators.required],
      amc_date: [''],
      current_amc_status: ['']
    });

    // If in edit mode, patch the form with existing data
    if (this.isEditMode) {
      this.loadAMC();
    }
  }


  loadAMC() {
    let postjson = {
      "mode": "single",
      "enquiry_id": this.enquiryId
    }
    console.log('postjson', postjson);
    this.apiService.post('get_amc_detail.php', postjson).subscribe({
      next: (res: any) => {
        console.log('signle amc', res);
        let result = res.data;
        this.amcForm.patchValue({
          client_name: result.client_name,
          contact_person: result.contact_person_name,
          contact_number: result.contact_no1,
          delivered_date: result.delivered_date,
          amc_date: result.amc_date,
          no_of_years: parseInt(result.amc_period),
          current_amc_status: result.amc_status
        });
      },
      error: err => {
        console.error('Failed to load enquiry', err);
      }
    });
  }

  calculateAmcDate(): void {
    const deliveredDate = this.amcForm.get('delivered_date')?.value;
    const noOfYears = this.amcForm.get('no_of_years')?.value;

    if (deliveredDate) {
      // ✅ Convert to yyyy-MM-dd
      const formattedDeliveredDate = this.datePipe.transform(deliveredDate, 'yyyy-MM-dd');
      this.amcForm.patchValue({ delivered_date: formattedDeliveredDate });

      // ✅ Calculate AMC Date if years selected
      if (noOfYears) {
        const amcDate = new Date(deliveredDate);
        amcDate.setFullYear(amcDate.getFullYear() + Number(noOfYears));

        const formattedAmcDate = this.datePipe.transform(amcDate, 'yyyy-MM-dd');
        this.amcForm.patchValue({ amc_date: formattedAmcDate });
      }
    }
  }


  onSubmit() {
    this.submitted = true;
    if (this.amcForm.valid) {
      const dialogRef = this.dialog.open(ConfirmDialogComponent, {
        width: '350px',
        data: { message: 'Are you sure you want to submit this AMC?' }
      });

      dialogRef.afterClosed().subscribe(result => {
        if (result) {
          this.SubmitAMC();
        }
      });
    } else {
      this.amcForm.markAllAsTouched();
    }
  }



  SubmitAMC() {
    if (this.amcForm.valid) {
      console.log(this.isEditMode ? 'Updating AMC...' : 'Creating AMC...', this.amcForm.value);
      let postjson = {
        "enquiry_id": this.enquiryId,
        "client_name": this.amcForm.value.client_name,
        "contact_person_name": this.amcForm.value.contact_person,
        "contact_no1": this.amcForm.value.contact_number,
        "delivered_date": this.amcForm.value.delivered_date,
        "amc_date": this.amcForm.value.amc_date,
        "amc_period": this.amcForm.value.no_of_years.toString(),
        "amc_status": this.amcForm.value.current_amc_status,
        "user": "Admin"
      }

      this.apiService.post('amc_update.php', postjson).subscribe({
        next: (res: any) => {
          console.log('amc submit res', res);
          this.snackBar.open(res.message, 'Close', {
            duration: 3000,
            verticalPosition: 'top',
            horizontalPosition: 'right',
          });
          this.router.navigate(['/amc-list']);
        }

      });
    }
  }

}