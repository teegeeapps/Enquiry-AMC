import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ApiService } from '../../services/api-service';
import { Router } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogComponent } from '../confirm-dialog/confirm-dialog';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
  selector: 'app-create-enquiry',
  standalone: false,
  templateUrl: './create-enquiry.html',
  styleUrl: './create-enquiry.scss'
})
export class CreateEnquiryComponent implements OnInit {
  enquiryForm!: FormGroup;
  submitted = false;
  enquiryStatusOptions: any;
  enquiryId: string | null = null;
  isEditMode = false;
  req_cat: any;
  sourceEnq: any;
  yearsList: number[] = Array.from({ length: 10 }, (_, i) => i + 1);
  constructor(private fb: FormBuilder, private apiService: ApiService, private router: Router,
    private http: HttpClient, private dialog: MatDialog, private cdr: ChangeDetectorRef, private snackBar: MatSnackBar) {
    const nav = this.router.getCurrentNavigation();
    const state = nav?.extras?.state as { enquiryId?: string };
    this.enquiryId = state?.enquiryId || null;
    this.isEditMode = !!this.enquiryId;
    console.log('state enquiry', state);
    console.log('this.isEditMode', this.isEditMode);

    this.enquiryForm = this.fb.group({
      client_name: [''],
      contact_person: [''],
      contact_number: [''],
      alt_contact_number: [''],
      email: [''],
      address: [''],
      requirement: [''],
      requirement_category: [''],
      source: [''],
      enquiry_date: [''],
      requested_delivery_date: [''],
      delivered_date: [''],
      enquiry_status: [''],
      no_of_years: [1],
      amc_date: [''],
      follow_up_dates: [''],
      follow_up_notes: ['']
    });
  }

  ngOnInit(): void {
    this.initForm();
    if (this.isEditMode && this.enquiryId) {
      this.loadEnquiryDetails(this.enquiryId);
    }

    this.req_cat = [
      {
        "id": 1,
        "name": "-- Select --"
      },
      {
        "id": 2,
        "name": "CCTV"
      },
      {
        "id": 3,
        "name": "New Fire Extinguisher"
      },
      {
        "id": 4,
        "name": "Refilling"
      }
    ];

    this.sourceEnq = [
      {
        "id": 1,
        "name": "-- Select --"
      },
      {
        "id": 2,
        "name": "Existing Customer"
      },
      {
        "id": 3,
        "name": "JustDial"
      },
      {
        "id": 4,
        "name": "Reference"
      },
      {
        "id": 5,
        "name": "Social Media"
      },

    ]
  }

  initForm() {
    this.enquiryForm = this.fb.group({
      client_name: ['', Validators.required],
      contact_person: ['', Validators.required],
      contact_number: ['', Validators.required],
      alt_contact_number: [''],
      email: [''],
      address: [''],
      requirement: ['', Validators.required],
      requirement_category: [''],
      source: [''],
      enquiry_date: [''],
      requested_delivery_date: [''],
      delivered_date: [''],
      no_of_years: [1],
      enquiry_status: [''],
      amc_date: [''],
      follow_up_dates: [''],
      follow_up_notes: ['']
    });
    this.loadEnquiryStatusOptions();
    if (this.isEditMode && this.enquiryId) {
      this.loadEnquiryDetails(this.enquiryId);
    }
  }


  loadEnquiryStatusOptions() {
    this.http.get<string[]>('data/enquiry_status.json')
      .subscribe({
        next: (response) => {
          console.log('response enq status', response);
          this.enquiryStatusOptions = response;
          this.cdr.detectChanges();
        },
        error: (err) => {
          console.error('Failed to load enquiry statuses', err);
        }
      });
  }

  loadEnquiryDetails(enquiryId: string) {
    this.apiService.post('get_enquiry_list.php', { enquiry_id: enquiryId }).subscribe({
      next: (res: any) => {
        console.log('signle enquiry', res);
        let result = res.data;
        this.enquiryForm.patchValue({
          client_name: result.client_name,
          contact_person: result.contact_person_name,
          contact_number: result.contact_no1,
          alt_contact_number: result.contact_no2,
          email: result.email_id,
          address: result.address,
          requirement: result.requirement,
          requirement_category: result.requirement_category,
          source: result.source_of_enquiry,
          enquiry_date: result.enquiry_date,
          requested_delivery_date: result.requested_delivery_date,
          delivered_date: result.delivered_date,
          enquiry_status: result.enquiry_status_id,
          amc_date: result.amc_date,
          follow_up_dates: result.follow_up_date,
          follow_up_notes: result.follow_up_notes
        });
      },
      error: err => {
        console.error('Failed to load enquiry', err);
      }
    });
  }


  onSubmit() {
    this.submitted = true;
    if (this.enquiryForm.valid) {
      const dialogRef = this.dialog.open(ConfirmDialogComponent, {
        width: '350px',
        data: { message: 'Are you sure you want to submit this enquiry?' }
      });

      dialogRef.afterClosed().subscribe(result => {
        if (result) {
          this.submitEnquiry();
        }
      });
    } else {
      this.enquiryForm.markAllAsTouched();
    }
  }

  submitEnquiry() {
    let enqID: any;
    if (this.isEditMode) {
      enqID = this.enquiryId
    } else {
      enqID = ""
    }
    let postjson = {
      "enquiry_id": enqID,
      "client_name": this.enquiryForm.value.client_name,
      "contact_person_name": this.enquiryForm.value.contact_person,
      "contact_no1": this.enquiryForm.value.contact_number,
      "contact_no2": this.enquiryForm.value.alt_contact_number,
      "email_id": this.enquiryForm.value.email,
      "address": this.enquiryForm.value.address,
      "requirement": this.enquiryForm.value.requirement,
      "requirement_category": this.enquiryForm.value.requirement_category,
      "source_of_enquiry": this.enquiryForm.value.source,
      "enquiry_date": this.enquiryForm.value.enquiry_date,
      "enquiry_status_id": parseInt(this.enquiryForm.value.enquiry_status),
      "follow_up_date": this.enquiryForm.value.follow_up_dates,
      "follow_up_notes": this.enquiryForm.value.follow_up_notes,
      "delivered_date": this.enquiryForm.value.delivered_date,
      "requested_delivery_date": this.enquiryForm.value.requested_delivery_date,
      "amc_date": this.enquiryForm.value.amc_date,
      "created_by": "Admin",
      "updated_by": "Admin"
    }
    console.log('postjson', postjson);

    let amcpost = {
      "enquiry_id": enqID,
      "client_name": this.enquiryForm.value.client_name,
      "contact_person_name": this.enquiryForm.value.contact_person,
      "contact_no1": this.enquiryForm.value.contact_number,
      "requirement_category": this.enquiryForm.value.requirement_category,
      "delivered_date": this.enquiryForm.value.delivered_date,
      "amc_date": this.enquiryForm.value.amc_date,
      "amc_period": this.enquiryForm.value.no_of_years,
      "amc_status": "Active",
      "user": "Admin"
    }

    if (this.enquiryForm.value.delivered_date == null) {
      this.apiService.post('enquiry_submit.php', postjson).subscribe((res: any) => {
        console.log(res, "res");
        this.snackBar.open(res.message, 'Close', {
          duration: 3000,
          verticalPosition: 'top',
          horizontalPosition: 'right',
        });
        this.router.navigate(['/enquiry-list']);
      });

      // send to API or handle logic here
    } else {
      console.log("this.enquiryForm.value.delivered_date", this.enquiryForm.value.delivered_date);
      this.apiService.post('enquiry_submit.php', postjson).subscribe((res: any) => {
        console.log(res, "res");
        this.apiService.post('amc_submit.php', amcpost).subscribe((res: any) => {
          console.log('amc_submit.', res);
        });
        this.snackBar.open(res.message, 'Close', {
          duration: 3000,
          verticalPosition: 'top',
          horizontalPosition: 'right',
        });
        this.router.navigate(['/enquiry-list']);
      });
    }



    // send to API or handle logic here
  }

  calculateAmcDate() {
    const deliveredDate: Date = this.enquiryForm.get('requested_delivery_date')?.value;
    const noOfYears: number = +this.enquiryForm.get('no_of_years')?.value;

    if (deliveredDate && noOfYears) {
      const amcDate = new Date(deliveredDate);
      amcDate.setFullYear(amcDate.getFullYear() + noOfYears);
      this.enquiryForm.get('amc_date')?.setValue(amcDate);
    }
  }

}