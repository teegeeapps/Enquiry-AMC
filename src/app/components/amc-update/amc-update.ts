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
    this.loadEnquiryDetails(this.enquiryId);
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
      this.loadAmcData();
    }
  }

  loadAmcData() {
    const amcData = {
      client_name: 'ABC Corp',
      contact_person: 'John Doe',
      contact_number: '9876543210',
      delivered_date: '2025-08-01',
      no_of_years: 2,
      amc_date: '2027-08-01',
      current_amc_status: 'Active'
    };
    this.amcForm.patchValue(amcData);
  }


  loadEnquiryDetails(enquiryId: any) {
    this.apiService.post('get_enquiry_list.php', { enquiry_id: enquiryId }).subscribe({
      next: (res: any) => {
        console.log('signle enquiry', res);
        let result = res.data;
        this.amcForm.patchValue({
          client_name: result.client_name,
          contact_person: result.contact_person_name,
          contact_number: result.contact_no1
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
        "requirement_category": "AMC Renewal",
        "delivered_date": this.amcForm.value.delivered_date,
        "amc_date": this.amcForm.value.amc_date,
        "amc_period": this.amcForm.value.no_of_years,
        "amc_status": "Active",
        "user": "Admin"
      }

      this.apiService.post('amc_submit.php', postjson).subscribe({
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