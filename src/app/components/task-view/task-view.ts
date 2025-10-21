import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api-service';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogComponent } from '../confirm-dialog/confirm-dialog';

@Component({
  selector: 'app-task-view',
  standalone: false,
  templateUrl: './task-view.html',
  styleUrl: './task-view.scss',
  providers: [DatePipe]
})
export class TaskViewComponent implements OnInit{
 assignForm!: FormGroup;
 enquiryId: string | null = null;
 taskId: string | null = null;
 submitted = false;
 assignment : any;
 enqData: any;
 isServicesec = false;
 user: any;
 adminview= false;
 techview = false;

  isEditMode = false; // Set to true if updating
  constructor(private fb: FormBuilder, private router: Router, private apiService: ApiService,
     private snackBar: MatSnackBar, private dialog: MatDialog, private datePipe: DatePipe){
    const nav = this.router.getCurrentNavigation();
    const state = nav?.extras?.state as { enquiryId?: string, editMode?: boolean, taskId?: string };
    this.enquiryId = state?.enquiryId || null;
    this.taskId = state?.taskId || null;
    this.isEditMode = state?.editMode || false;
    console.log('this.enquiryId', this.enquiryId);
     console.log('this.taskId', this.taskId);
    console.log('Edit Mode:', this.isEditMode);
}

ngOnInit(): void {
   let user: any;
    let postjson: any;
    user = localStorage.getItem('user');
    this.user = JSON.parse(user);
    console.log("this.user", this.user);
    if (this.user.role_name == "Admin") {
      this.adminview = true;
      this.techview = false;
    } else {
      this.adminview = false;
      this.techview = true;
    }
  this.assignForm = this.fb.group({
  client_name: ['', Validators.required],
  contact_person: ['', Validators.required],
  contact_number: ['', Validators.required],
  address: [''],
  delivery_instructions: [''],
  technician_instructions: [''],
  customer_location: [''],
  visit_date: [''],
  assigned_for: [''],
  completed_status: [[], Validators.required], // multi-select
});

/* this.assignForm.get('visit_date')?.valueChanges.subscribe(status => {
      console.log('status', status);
       let formattedDate = this.datePipe.transform(status, 'yyyy-MM-dd') || '';
         console.log('formattedDate', formattedDate);
}); */

/* this.assignForm.get('completed_status')?.valueChanges.subscribe(status => {
      console.log('status', status);
      const serviceDateControl = this.assignForm.get('service_date');
      if (status == "Assign For Service") {
        this.isServicesec = true;
        serviceDateControl?.setValidators([Validators.required]);
      } else {
        this.isServicesec = false;
        serviceDateControl?.clearValidators();
      }
      serviceDateControl?.updateValueAndValidity();}) */
      this.loadTask();
  }

  loadTask(){
    let postjson = {
      "mode": "get_enquiry",
      "enquiry_id": this.enquiryId
    }
    console.log('postjson', postjson);
    this.apiService.post('assign_technician.php', postjson).subscribe({
      next: (res: any) => {
        console.log('signle enquiry', res);
        let result = res.data.enquiry;
        this.enqData = res.data.enquiry;
        this.assignment = res.data.assignments.find((a: any) => a.assignment_id == this.taskId);
        console.log('assignment', this.assignment);
        this.assignForm.patchValue({
          client_name: result.client_name,
          contact_person: result.contact_person_name,
          contact_number: result.contact_no1,
          address: result.address,
          assigned_for: this.assignment.ass_type,
          delivery_instructions: this.assignment.delivery_instructions,
          technician_instructions: this.assignment.technician_instructions,
          customer_location: this.assignment.customer_location,
          visit_date: res.data.visit_history[0].visit_date,
          completed_status: this.assignment.completed_status
        });
      },
      error: err => {
        console.error('Failed to load enquiry', err);
      }
    });
  }

 /*    onSubmit() {
     console.log("this.assignForm.value", this.assignForm.value);
    } */

      onSubmit() {
         this.submitted = true;
         console.log('this.assignForm', this.assignForm);
         console.log('this.assignForm.valid', this.assignForm.valid);
         if (this.assignForm.valid) {
           const dialogRef = this.dialog.open(ConfirmDialogComponent, {
             width: '350px',
             data: { message: 'Are you sure you want to submit?' }
           });
     
           dialogRef.afterClosed().subscribe(result => {
             if (result) {
               this.UpdateTask();
             }
           });
         } else {
           this.assignForm.markAllAsTouched();
         }
       }


  UpdateTask() {
    let enqID: any;
    let empNo = this.assignment.employee_number;
    console.log('empNo', empNo);
    let completed_status: any;
    if(this.assignForm.value.completed_status == "Pending"){
      completed_status = 1;
    } else if(this.assignForm.value.completed_status == "Assign For Service") {
      completed_status = 2;
    } else {
      completed_status = 3;
    }
    let postjson = {
      "mode": "update",
      "id": this.taskId,
      "enquiry_id": this.enquiryId,
      "assignment_type": this.assignment.ass_type,
      "technicians": [empNo],
      "delivery_instructions": this.assignForm.value.delivery_instructions,
      "technician_instructions": this.assignForm.value.technician_instructions,
      "customer_location": this.assignForm.value.customer_location,
      "assigned_by": this.assignment.assigned_by,
      "technician_status": { 
        [this.assignment.employee_number]: completed_status
      }
    }
    console.log('postjson', postjson);

      this.apiService.post('assign_technician.php', postjson).subscribe((res: any) => {
        console.log(res, "res");
        if(this.assignment.completed_status!=="Assign For Service" && this.assignForm.value.completed_status == "Assign For Service"){
          this.createService();
        }
        this.snackBar.open(res.message, 'Close', {
          duration: 3000,
          verticalPosition: 'top',
          horizontalPosition: 'right',
        });
        this.router.navigate(['/task-list']);
      }); 

      // send to API or handle logic here
    } 

    createService(){
      const today = new Date();
      let formattedDate = this.datePipe.transform(this.assignForm.value.visit_date, 'yyyy-MM-dd') || '';
      let postjson = {
        "mode": "INSERT",
        "enquiry_id": this.enquiryId,
        "assignment_id": this.assignment.assignment_id, 
        "client_name": this.enqData.client_name,
        "contact_person_name":  this.enqData.contact_person_name,
        "contact_no1": this.enqData.contact_no1,
        "service_status": "Pending",
        "service_date": formattedDate,
        "created_by": "admin"
      }

      this.apiService.post('service_list.php', postjson).subscribe((res: any) => {
        console.log('create service', res);
      });
    }

  

}
