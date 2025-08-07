import { Component, OnInit  } from '@angular/core';
import { FormBuilder, FormGroup, Validators} from '@angular/forms';
import { Router  } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { ApiService } from '../../services/api-service';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogComponent } from '../confirm-dialog/confirm-dialog';
import { MatSnackBar } from '@angular/material/snack-bar';

@Component({
  selector: 'app-add-employee',
  standalone: false,
  templateUrl: './add-employee.html',
  styleUrl: './add-employee.scss'
})
export class AddEmployeeComponent implements OnInit{
   employeeForm!: FormGroup;
   submitted = false;
   isEditMode = false;
   employeeId: string | null = null;
   empRoles: any;
   hidePassword = true;
   constructor(private fb: FormBuilder,private http: HttpClient, private apiService: ApiService, 
    private router: Router, private dialog: MatDialog, private snackBar: MatSnackBar) {
    const nav = this.router.getCurrentNavigation();
    const state = nav?.extras?.state as { employeeId?: string };
    this.employeeId = state?.employeeId || null;
    this.isEditMode = !!this.employeeId;
    console.log('state', state);
    this.getroles();
   }

  ngOnInit(): void {
      this.employeeForm = this.fb.group({
      employeeName: ['', Validators.required],
      employeeNumber: ['', Validators.required],
      contactNumber: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      status: ['', Validators.required],
      password: ['', [Validators.required, Validators.minLength(6)]],
      role: ['', Validators.required],
    });
    if (this.isEditMode && this.employeeId) {
      this.loadEmployeeData(this.employeeId);
    }
  }

  getroles(){
    let postjson = {
      "action": "get"
    }
     this.apiService.post<any>('roles_api.php', postjson) .subscribe((data: any) => {
      console.log('roles data', data);
      this.empRoles = data.roles;
     });

     
  }

  loadEmployeeData(employeeId: any): void {
    let postjson ={ employee_number: employeeId};
    console.log('postjosn', postjson);
    this.apiService.post<any>('get_individual_employee.php', postjson)
      .subscribe((data: any) => {
        console.log('employee_data', data);
        this.employeeForm.patchValue({
          employeeName: data.profile.employee_name,
          employeeNumber: data.profile.employee_number,
          contactNumber: data.profile.contact_no,
          email: data.profile.email_id,
          password: data.profile.password,
          status: data.profile.is_active == 1,
          role: data.profile.role_id
        });
      });
  }

  onSubmit() {
       this.submitted = true;
      if (this.employeeForm.valid) {
        const dialogRef = this.dialog.open(ConfirmDialogComponent, {
          width: '350px',
          data: { message: 'Are you sure you want to submit the details?' }
        });
  
        dialogRef.afterClosed().subscribe(result => {
          if (result) {
            this.submitDetails();
          }
        });
      } else{
        this.employeeForm.markAllAsTouched();
      }
    }

  submitDetails(): void {
    this.submitted = true;
    let postjson = {
      "employee_name": this.employeeForm.value.employeeName,
      "employee_number": this.employeeForm.value.employeeNumber,
      "contact_no" :this.employeeForm.value.contactNumber,
      "email_id" : this.employeeForm.value.email,
      "status" : this.employeeForm.value.status,
      "password" : this.employeeForm.value.password,
      "role_id": this.employeeForm.value.role,
      "created_by":  "Admin",
      "updated_by":  "Admin"
    }
    console.log('this.employeeForm', this.employeeForm.value);
    console.log('postjson', postjson);
    const url = this.isEditMode
      ? 'updateEmployee.php'
      : 'insertEmployee.php';
    if (this.employeeForm.valid) {
      this.apiService.post(url, postjson)
        .subscribe({
          next: (response) => {
            alert(this.isEditMode ? 'Employee updated!' : 'Employee added!');
            this.employeeForm.reset();
            this.router.navigate(['/employee-list']);
          },
          error: (err) => {
            console.error('Error adding employee:', err);
            alert('Failed to add employee.');
          }
        });
    } else {
      this.employeeForm.markAllAsTouched();
    }
  }

togglePasswordVisibility(): void {
  this.hidePassword = !this.hidePassword;
}
  }

