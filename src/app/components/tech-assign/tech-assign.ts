import { Component } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api-service';
import { MatSnackBar } from '@angular/material/snack-bar';

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
    this.apiService.post('assign_technician.php', { enquiry_id: enquiryId }).subscribe({
      next: (res: any) => {
        console.log('signle enquiry', res);
        this.employeeData = res.technician_list;
        this.assignment = res.assignment_details;
        this.visit_history = res.visit_history;
        if(this.assignment!== " " && this.assignment!== null){
        this.assignForm.patchValue({
            delivery_instructions: this.assignment.delivery_instructions,
            customer_location: this.assignment.customer_location,
            assigned_to: this.assignment.technician_employee_id
         });
        }
        this.assignForm.patchValue({
          client_name: res.enquiry_details.client_name,
          contact_person: res.enquiry_details.contact_person_name,
          contact_number: res.enquiry_details.contact_no1,
          address: res.enquiry_details.address
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
        "enquiry_id": this.enquiryId,
        "technician_employee_id": this.assignForm.value.assigned_to,
        "delivery_instructions": this.assignForm.value.delivery_instructions,
        "customer_location": this.assignForm.value.customer_location,
        "assigned_by": "Admin",
        "visit_date": this.assignForm.value.visit_date
      }
      this.apiService.post('assign_technician.php', postjson).subscribe((res: any)=>{
        this.snackBar.open("Technician assigned successfully", 'Close', {
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
