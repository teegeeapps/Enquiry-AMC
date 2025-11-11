import { Component, OnInit  } from '@angular/core';
import {
  FormBuilder,
  FormGroup,
  Validators
} from '@angular/forms';
import { Router  } from '@angular/router';
import { ApiService } from '../../services/api-service';
import { UserService } from '../../services/user-service/user-service';
import { MatSnackBar } from '@angular/material/snack-bar';
import { NgZone } from '@angular/core';

@Component({
  selector: 'app-login',
  standalone: false,
  templateUrl: './login.html',
  styleUrl: './login.scss'
})
export class LoginComponent implements OnInit{
    loginForm!: FormGroup;
    submitted = false;
    hidePassword = true;
    errorMessage = '';
  constructor(private apiService: ApiService, private fb: FormBuilder, private router: Router, 
    private userService: UserService, private snackBar: MatSnackBar, private ngZone: NgZone) {
    
  }

  ngOnInit(): void {
     localStorage.removeItem("user");
    this.loginForm = this.fb.group({
     mobileno: ['', [
      Validators.required,
      Validators.pattern(/^[0-9]{10}$/) // exactly 10 digits
    ]],
      password: ['', Validators.required],
      /* role: ['', Validators.required], */
     /*  rememberMe: [false] */
    });
    const userData = localStorage.getItem('user');
    if (userData) {
      const parsedUser = JSON.parse(userData);
      const role = parsedUser.role_name?.toLowerCase();
      if (role === 'Admin') {
        this.router.navigate(['/enquiry-list']);
      } else if (role === 'Technician') {
        this.router.navigate(['/task-list']);
      }
    }
  }

  login(): void {
    this.submitted = true;
    console.log("formvalue", this.loginForm.value);
    let postjson = {
      "contact_no": this.loginForm.value.mobileno,
      "password": this.loginForm.value.password
    }

    if (this.loginForm.invalid) {
    return;
    } else {
      this.apiService.post('login.php', postjson).subscribe((res: any) => {
        console.log(res);
        let userjson = {
          "employee_number" : res.employee_number
        }

         if(res.status == 1){
          this.apiService.post('get_individual_employee.php', userjson).subscribe((data: any) => {
           console.log('empresult', data);
            localStorage.removeItem("user");
           localStorage.setItem("user", JSON.stringify(data.profile));
           this.userService.setUsername(data.profile.employee_name); 
           this.userService.setRole(data.profile.role_name); 
           if(data.profile.role_name == 'Admin'){
            console.log('inside role admin');
              Promise.resolve().then(() => {
                this.ngZone.run(() => this.router.navigate(['/enquiry-list']));
              });
           } else if(data.profile.role_name == 'Technician'){
            console.log('inside role technician');
              Promise.resolve().then(() => {
              this.ngZone.run(() => this.router.navigate(['/task-list']));
            }); 
           } 
        });
         }
          else{
             this.snackBar.open(res.message, 'Close', {
            duration: 3000,
            verticalPosition: 'top',
            horizontalPosition: 'right',
          });
           }

        
      });
   
    }
  }

  forgotPassword(): void {
    alert('Redirect to forgot password flow.');
  }

  togglePasswordVisibility(): void {
  this.hidePassword = !this.hidePassword;
}

onlyNumbers(event: KeyboardEvent): boolean {
  const charCode = event.charCode ? event.charCode : event.keyCode;
  // Allow only digits (0-9)
  return charCode >= 48 && charCode <= 57;
}
}