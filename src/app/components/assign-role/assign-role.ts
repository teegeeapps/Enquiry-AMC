import { Component, OnInit  } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ApiService } from '../../services/api-service';

@Component({
  selector: 'app-assign-role',
  standalone: false,
  templateUrl: './assign-role.html',
  styleUrl: './assign-role.scss'
})
export class AssignRoleComponent implements OnInit {
  assignRoleForm!: FormGroup;
  submitted = false;
  users: any[] = [];
  roles: string[] = ['Admin', 'Manager', 'Technician', 'Viewer'];

  constructor(private fb: FormBuilder, private apiService: ApiService, ) {}

  ngOnInit(): void {
    this.assignRoleForm = this.fb.group({
      userId: ['', Validators.required],
      role: ['', Validators.required]
    });

    // Load user list from API
    this.apiService.get('/api/users').subscribe((res: any) => {
      this.users = res;
    });
  }

  onSubmit() {
    this.submitted = true;

    if (this.assignRoleForm.invalid) return;

    this.apiService.post('/api/assign-role', this.assignRoleForm.value).subscribe({
      next: () => alert('Role assigned successfully!'),
      error: () => alert('Error assigning role.')
    });
  }
}