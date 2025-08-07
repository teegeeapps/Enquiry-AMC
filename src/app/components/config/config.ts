import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ApiService } from '../../services/api-service';

@Component({
  selector: 'app-config',
  standalone: false,
  templateUrl: './config.html',
  styleUrl: './config.scss'
})
export class ConfigComponent implements OnInit {
  configForm!: FormGroup;
  logoPreview: string | ArrayBuffer | null = null;
  submitted = false;

  constructor(private fb: FormBuilder, private apiService: ApiService) {}

  ngOnInit(): void {
    this.configForm = this.fb.group({
      appTitle: ['', Validators.required],
      logo: [null],
      roles: ['', Validators.required],
      enquiryStatuses: ['', Validators.required]
    });

    this.loadConfig();
  }

  loadConfig() {
    this.apiService.get('/api/app-config').subscribe((data: any) => {
      this.configForm.patchValue({
        appTitle: data.appTitle,
        roles: data.roles.join(', '),
        enquiryStatuses: data.enquiryStatuses.join(', ')
      });

      this.logoPreview = data.logoUrl;
    });
  }

  onLogoChange(event: any) {
    const file = event.target.files[0];
    if (file) {
      this.configForm.patchValue({ logo: file });

      const reader = new FileReader();
      reader.onload = e => this.logoPreview = reader.result;
      reader.readAsDataURL(file);
    }
  }

  onSubmit() {
    this.submitted = true;
    if (this.configForm.invalid) return;

    const formData = new FormData();
    formData.append('appTitle', this.configForm.get('appTitle')?.value);
    formData.append('roles', this.configForm.get('roles')?.value);
    formData.append('enquiryStatuses', this.configForm.get('enquiryStatuses')?.value);
    
    const logoFile = this.configForm.get('logo')?.value;
    if (logoFile) formData.append('logo', logoFile);

    this.apiService.post('/api/save-config', formData).subscribe({
      next: () => alert('Configuration updated successfully!'),
      error: () => alert('Error saving configuration')
    });
  }
}