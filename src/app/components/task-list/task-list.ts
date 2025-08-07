import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { ApiService } from '../../services/api-service';
import { Router } from '@angular/router';

interface Task {
  client_name: string;
  contact_person: string;
  contact_number: string;
  address: string;
  delivery_instructions: string;
  customer_location: string;
  visit_date: string;
  completed: string;
}
@Component({
  selector: 'app-task-list',
  standalone: false,
  templateUrl: './task-list.html',
  styleUrl: './task-list.scss'
})
export class TaskListComponent {
  tasks: any;
  user: any;
  taskList: any[] = [];
  taskColumns: string[] = [];
constructor(private apiService: ApiService, private cdr: ChangeDetectorRef, private router: Router) {}
  saveTasks() {
    console.log('Tasks Updated:', this.taskList);
    // You can send this to API/backend here
  }

  
  ngOnInit(): void {
    let user: any;
    user = localStorage.getItem('user');
    this.user = JSON.parse(user);
    console.log("this.user", this.user);
    let postjson = {
      "technician_id" : this.user.employee_number
    }
    this.apiService.post<any[]>('get_enquiry_list.php', postjson).subscribe((res: any) => {
      console.log('this.enqData', res);
        if (res && res.data.length > 0) {
          this.taskList = res.data;
          console.log('this.taskList', this.taskList);
          setTimeout(() => {
            this.taskColumns = Object.keys(this.taskList[0]);
            this.taskColumns.push('Completed');   // 🔑 Extract column names
            console.log('this.taskColumns', this.taskColumns );
              this.cdr.detectChanges();
          });
        } else{
           this.taskList = [];
           this.taskColumns = ["enquiry_id", "client_name", "contact_person_name", "contact_no1",  "enquiry_date", "status_name", "technician_name"];
            console.log('this.taskList', this.taskList);
            console.log('this.taskColumns', this.taskColumns);
             this.cdr.detectChanges();
        }
      });
       
    }

    onViewtask(task: any) {
    }

    onEdittask(task: any) {
    
    }

}
