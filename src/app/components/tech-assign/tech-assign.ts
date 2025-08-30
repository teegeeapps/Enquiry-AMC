import { Component } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api-service';
import { MatSnackBar } from '@angular/material/snack-bar';
import moment from 'moment';
import { formatDate, DatePipe } from '@angular/common';

@Component({
  selector: 'app-tech-assign',
  standalone: false,
  templateUrl: './tech-assign.html',
  styleUrl: './tech-assign.scss'
})
export class TechAssignComponent {
   assignForm: FormGroup;
   submitted = false;
   enquiryId: string | null = null;
   employeeData: any;
   assignment: any;
   visit_history: any;
   enquiry: any;

  constructor(private fb: FormBuilder, private router: Router, private apiService: ApiService, private snackBar: MatSnackBar) {
    const nav = this.router.getCurrentNavigation();
        const state = nav?.extras?.state as { enquiryId?: string };
        this.enquiryId = state?.enquiryId || null;
        console.log('state enquiry', state);

        this.assignForm = this.fb.group({
        client_name: ['', Validators.required],
        contact_person: ['', Validators.required],
        contact_number: ['', Validators.required],
        address: [''],
        delivery_instructions: [''],
        customer_location: [''],
        visit_date: ['', Validators.required],
        assigned_for: ['', Validators.required],
        assigned_to: [[], Validators.required], // multi-select
      });

     if (this.enquiryId) {
      this.loadEnquiryDetails(this.enquiryId);
    }
  }

  loadEnquiryDetails(enquiryId: string) {
    let postjso ={
      "mode": "get_enquiry",
      "enquiry_id": enquiryId
    }
    this.apiService.post('assign_technician.php', postjso).subscribe({
      next: (res: any) => {
        console.log('signle enquiry', res);
        this.employeeData = res.data.technician_list;
        console.log('this.employeeData', this.employeeData);
        this.enquiry = res.data.enquiry;
        console.log('this.enquiry', this.enquiry);
        if(res.data.assignments!== undefined && res.data.assignments.length > 0){
           this.assignment = res.data.assignments[0];
        console.log('this.assignment', this.assignment);
        this.visit_history = res.data.visit_history;
        if(this.assignment!== " " && this.assignment!== null){
        this.assignForm.patchValue({
            delivery_instructions: this.assignment.delivery_instructions,
            customer_location: this.assignment.customer_location,
            assigned_to: res.data.assignments.map((a: any) => a.employee_number),
            assigned_for: this.assignment.ass_type,
            visit_date: this.visit_history[0].visit_date
         });
        }
        }
       
        this.assignForm.patchValue({
          client_name: this.enquiry.client_name,
          contact_person: this.enquiry.contact_person_name,
          contact_number: this.enquiry.contact_no1,
          address: this.enquiry.address
        });
      },
      error: err => {
        console.error('Failed to load enquiry', err);
      }
    });
  }


  onSubmit() {
     console.log('Assignment Data:', this.assignForm.value);
    if (this.assignForm.valid) {
      console.log('Assignment Data:', this.assignForm.value);
      let postjson = {
        "mode": "insert",
        "enquiry_id": this.enquiryId,
        "assignment_type": this.assignForm.value.assigned_for,
        "technicians": this.assignForm.value.assigned_to,
        "delivery_instructions": this.assignForm.value.delivery_instructions,
        "customer_location": this.assignForm.value.customer_location,
        "assigned_by": "Admin",
        "visit_date":  formatDate(this.assignForm.value.visit_date, 'yyyy-MM-dd', 'en-IN')
      }

      console.log("postjson", postjson);
      this.apiService.post('assign_technician.php', postjson).subscribe((res: any)=>{
        this.snackBar.open(res.message, 'Close', {
        duration: 3000,
        verticalPosition: 'top',
        horizontalPosition: 'right',
      });
        this.router.navigate(['/enquiry-list']);
      });
      // Submit logic or API call here
    } else {
      this.assignForm.markAllAsTouched();
    }
  }

  // Remove technician from multi-select
removeTechnician(techId: any) {
  const current = this.assignForm.value.assigned_to as any[];
  this.assignForm.patchValue({
    assigned_to: current.filter(t => t !== techId)
  });
}

// Get technician name for chip display
getTechnicianName(id: any) {
  return this.employeeData.find((emp: any) => emp.employee_id === id)?.employee_name || '';
}

getEmployeeName(id: number) {
  return this.employeeData.find((e:any) => e.employee_id === id)?.employee_name || '';
}

removeEmployee(empId: number) {
  const selected = this.assignForm.get('assigned_to')?.value || [];
  this.assignForm.get('assigned_to')?.setValue(selected.filter((id: number) => id !== empId));
}

}
