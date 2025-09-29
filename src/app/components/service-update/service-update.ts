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
  selector: 'app-service-update',
  standalone: false,
  templateUrl: './service-update.html',
  styleUrl: './service-update.scss',
  providers: [DatePipe]
})
export class ServiceUpdateComponent implements OnInit{

  serviceForm!: FormGroup;
  submitted = false;
  enquiryStatusOptions: any;
  serviceId: string | null = null;
  amc_id: string | null = null;
  isEditMode = false; // Set to true if updating
  yearsList: number[] = Array.from({ length: 10 }, (_, i) => i + 1);

   constructor(private fb: FormBuilder, private router: Router, private apiService: ApiService,
    private datePipe: DatePipe, private dialog: MatDialog, private snackBar: MatSnackBar, private http: HttpClient,) {
    const nav = this.router.getCurrentNavigation();
    const state = nav?.extras?.state as { service_Id?: string, amc_id?: string };
    this.serviceId = state?.service_Id || null;
    this.amc_id = state?.amc_id || null;
    }

  ngOnInit(): void {
    this.serviceForm = this.fb.group({
      client_name: ['', Validators.required],
      contact_person: ['', Validators.required],
      contact_number: ['', Validators.required],
     /*  delivered_date: ['', Validators.required], */
      service_date: [''],
      service_status: [''],
      /* follow_up_dates: [''],
      follow_up_notes: ['', Validators.required],
      follow_up_his: [''] */
    });
      this.loadService();
}


loadService(){
  let postjson = {
      "mode": "FETCH_ONE",
      "id": this.serviceId
    }
    console.log('postjson', postjson);
    this.apiService.post('service_list.php', postjson).subscribe({
      next: (res: any) => {
        console.log('signle service', res);
        let result = res.data;
        this.serviceForm.patchValue({
          client_name: result.client_name,
          contact_person: result.contact_person_name,
          contact_number: result.contact_no1,
          /* delivered_date: result.delivered_date, */
          service_date: result.service_date,
          service_status: result.service_status,
          /* follow_up_dates: result.latest_followup_date,
          follow_up_notes: result.latest_followup_notes, */
          follow_up_his: res.followup_text
        });
      },
      error: err => {
        console.error('Failed to load enquiry', err);
      }
    });
}

  onSubmit() {
    this.submitted = true;
    if (this.serviceForm.valid) {
      const dialogRef = this.dialog.open(ConfirmDialogComponent, {
        width: '350px',
        data: { message: 'Are you sure you want to submit this AMC?' }
      });

      dialogRef.afterClosed().subscribe(result => {
        if (result) {
          this.SubmitService();
        }
      });
    } else {
      this.serviceForm.markAllAsTouched();
    }
  }

  SubmitService(){
    if (this.serviceForm.valid) {
      console.log(this.isEditMode ? 'Updating AMC...' : 'Creating AMC...', this.serviceForm.value);
      let postjson = {
        "mode": "UPDATE",
        "id": this.serviceId,
        "client_name": this.serviceForm.value.client_name,
        "contact_person_name": this.serviceForm.value.contact_person,
        "contact_no1": this.serviceForm.value.contact_number,
        /* "delivered_date": this.serviceForm.value.delivered_date, */
        "service_date": this.serviceForm.value.service_date,
        "service_status": this.serviceForm.value.service_status,
       /*  "followup_date": this.serviceForm.value.follow_up_dates,
        "followup_notes": this.serviceForm.value.follow_up_notes, */
        "user": "Admin"
      }

      this.apiService.post('service_list.php', postjson).subscribe({
        next: (res: any) => {
          console.log('amc submit res', res);
          this.snackBar.open(res.message, 'Close', {
            duration: 3000,
            verticalPosition: 'top',
            horizontalPosition: 'right',
          });
          this.router.navigate(['/service-list']);
        }

      });
    }
  }


}
