import { Component, OnInit  } from '@angular/core';
import {
  FormBuilder,
  FormGroup,
  Validators
} from '@angular/forms';
import { Router  } from '@angular/router';
import { ApiService } from '../../services/api-service';
import { UserService } from '../../services/user-service/user-service';

@Component({
  selector: 'app-login',
  standalone: false,
  templateUrl: './login.html',
  styleUrl: './login.scss'
})
export class LoginComponent implements OnInit{
    loginForm!: FormGroup;
    roles = ['Admin', 'User', 'Manager'];
    submitted = false;
    hidePassword = true;
  constructor(private apiService: ApiService, private fb: FormBuilder, private router: Router, 
    private userService: UserService) {
    
  }

  ngOnInit(): void {
     localStorage.removeItem("user");
    this.loginForm = this.fb.group({
      username: ['', Validators.required],
      password: ['', [Validators.required, Validators.minLength(6)]],
      /* role: ['', Validators.required], */
     /*  rememberMe: [false] */
    });
    const userData = localStorage.getItem('user');
    if (userData) {
      // ✅ User is already logged in, redirect to dashboard
      this.router.navigate(['/dashboard']);
    } 
  }

  login(): void {
    this.submitted = true;
    console.log("formvalue", this.loginForm.value);
    let postjson = {
      "email": this.loginForm.value.username,
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
        this.apiService.post('get_individual_employee.php', userjson).subscribe((data: any) => {
           console.log('empresult', data);
           localStorage.removeItem("user");
           localStorage.setItem("user", JSON.stringify(data.profile));
           this.userService.setUsername(data.profile.employee_name); 
           this.userService.setRole(data.profile.role_name); 
           if(data.profile.role_name == 'Admin'){
              this.router.navigate(['/enquiry-list']);
           } else {
              this.router.navigate(['/task-list']);
           }
           
        });
      });
   
    }
  }

  forgotPassword(): void {
    alert('Redirect to forgot password flow.');
  }

  togglePasswordVisibility(): void {
  this.hidePassword = !this.hidePassword;
}
}