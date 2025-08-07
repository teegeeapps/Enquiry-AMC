import { Component, Input  } from '@angular/core';

@Component({
  selector: 'app-view-employee',
  standalone: false,
  templateUrl: './view-employee.html',
  styleUrl: './view-employee.scss'
})
export class ViewEmployeeComponent {
  @Input() employee: any;
}