import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api-service';
import { DatePipe } from '@angular/common';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogComponent } from '../confirm-dialog/confirm-dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { HttpClient } from '@angular/common/http';

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
  singleAmc: any;
   enquiryStatusOptions: any;
  amc_id: string | null = null;
  isEditMode = false; // Set to true if updating
  yearsList: number[] = Array.from({ length: 10 }, (_, i) => i + 1);
  constructor(private fb: FormBuilder, private router: Router, private apiService: ApiService,
    private datePipe: DatePipe, private dialog: MatDialog, private snackBar: MatSnackBar, private http: HttpClient,) {
    const nav = this.router.getCurrentNavigation();
    const state = nav?.extras?.state as { amc_id?: string, editMode?: boolean };
    this.amc_id = state?.amc_id || null;
    this.isEditMode = state?.editMode || false;
    console.log('this.amc_id', this.amc_id);
    console.log('Edit Mode:', this.isEditMode);
  }

  ngOnInit(): void {
    this.amcForm = this.fb.group({
      client_name: ['', Validators.required],
      contact_person: ['', Validators.required],
      contact_number: ['', Validators.required],
      delivered_date: ['', Validators.required],
      no_of_years: ['', Validators.required],
      refilling_date: [''],
      current_refilling_status: [''],
      follow_up_dates: [''],
      follow_up_notes: ['', Validators.required],
      follow_up_his: ['']
    });

    // If in edit mode, patch the form with existing data
    this.loadEnquiryStatusOptions();
    if (this.isEditMode) {
      this.loadAMC();
    }
  }


  loadAMC() {
    let postjson = {
      "mode": "single",
      "amc_id": this.amc_id
    }
    console.log('postjson', postjson);
    this.apiService.post('get_amc_detail.php', postjson).subscribe({
      next: (res: any) => {
        console.log('signle amc', res);
        let result = res.data;
        this.singleAmc = res.data;
        this.amcForm.patchValue({
          client_name: result.client_name,
          contact_person: result.contact_person_name,
          contact_number: result.contact_no1,
          delivered_date: result.delivered_date,
          refilling_date: result.refilling_date,
          no_of_years: parseInt(result.refilling_period),
          current_refilling_status: result.refilling_status,
          follow_up_dates: result.latest_followup_date,
          follow_up_notes: result.latest_followup_notes,
          follow_up_his: res.followup_text
        });
      },
      error: err => {
        console.error('Failed to load enquiry', err);
      }
    });
  }

   loadEnquiryStatusOptions() {
    this.apiService.post('get_status_list.php', {"mode": "amc"})
      .subscribe({
        next: (response: any) => {
          console.log('response enq status', response);
          this.enquiryStatusOptions = response.data;
        },
        error: (err) => {
          console.error('Failed to load enquiry statuses', err);
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
        this.amcForm.patchValue({ refilling_date: formattedAmcDate });
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
        "amc_id": this.singleAmc.amc_id,
        "enquiry_id": this.singleAmc.enquiry_id,
        "client_name": this.amcForm.value.client_name,
        "contact_person_name": this.amcForm.value.contact_person,
        "contact_no1": this.amcForm.value.contact_number,
        "delivered_date": this.amcForm.value.delivered_date,
        "refilling_date": this.amcForm.value.refilling_date,
        "refilling_period": this.amcForm.value.no_of_years.toString(),
        "refilling_status": this.amcForm.value.current_refilling_status,
        "followup_date":  this.datePipe.transform(this.amcForm.value.follow_up_dates, 'yyyy-MM-dd'),
        "followup_notes": this.amcForm.value.follow_up_notes,
        "user": "Admin"
      }

      this.apiService.post('amc_update.php', postjson).subscribe({
        next: (res: any) => {
          console.log('amc submit res', res);
          if(this.singleAmc.refilling_status!== "Order Delivered" && this.amcForm.value.current_refilling_status == "Order Delivered"){
            this.createAMC();
          }
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

  createAMC(){
      let amcpost = {
      "enquiry_id": this.singleAmc.enquiry_id,
      "client_name": this.amcForm.value.client_name,
      "contact_person_name": this.amcForm.value.contact_person,
      "contact_no1": this.amcForm.value.contact_number,
      "requirement_category": this.singleAmc.requirement_category,
      "delivered_date": this.amcForm.value.delivered_date,
      "refilling_date": this.getFutureDate1(this.amcForm.value.no_of_years),
      "refilling_period": this.amcForm.value.no_of_years,
      "refilling_status": "Refilling Order Received",
      "user": "Admin"
    }

    console.log('amc submit', amcpost);

    this.apiService.post('amc_submit.php', amcpost).subscribe((res: any) => {
            console.log('amc_submit.', res);
     });
  }


  getFutureDate(yearsToAdd: number): string {
  const today = new Date();
  const futureDate = new Date(today.setFullYear(today.getFullYear() + yearsToAdd));

  // Format as dd-MM-yyyy
  const formatted = `${futureDate.getDate().toString().padStart(2, '0')}-${(futureDate.getMonth() + 1)
    .toString()
    .padStart(2, '0')}-${futureDate.getFullYear()}`;

  return formatted;
}


getFutureDate1(yearsToAdd: number): string {
  const today = new Date();

  // Create a new Date object so we don't mutate today
  const futureDate = new Date(today.getTime());
  futureDate.setFullYear(futureDate.getFullYear() + yearsToAdd);

  // Format as yyyy-MM-dd
  const year = futureDate.getFullYear();
  const month = (futureDate.getMonth() + 1).toString().padStart(2, '0'); // Months are 0-based
  const day = futureDate.getDate().toString().padStart(2, '0');

  return `${year}-${month}-${day}`;
}

}