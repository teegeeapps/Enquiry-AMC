import { Component } from '@angular/core';

@Component({
  selector: 'app-dashboard',
  standalone: false,
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.scss'
})
export class DashboardComponent {
 userData = [
    { name: 'Alice', age: 25, job: 'Developer', email: "alice@gmail.com",  dob: 'Developer'},
    { name: 'Bob', age: 30, job: 'Designer', email: "bob@gmail.com", dob: 'Developer' },
    { name: 'Charlie', age: 35, job: 'Manager', email:"charlie@gmail.com", dob: 'Developer' },
    { name: 'John', age: 30, job: 'Designer',  email: "john@gmail.com", dob: 'Developer'},
    { name: 'Rose', age: 35, job: 'Manager', email:"rose@gmail.com", dob: 'Developer' }
  ];

  userColumns = ['name', 'age', 'job', 'email', 'dob'];
}
