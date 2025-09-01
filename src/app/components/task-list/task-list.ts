import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { ApiService } from '../../services/api-service';
import { Router } from '@angular/router';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogComponent } from '../confirm-dialog/confirm-dialog';
import { MatSnackBar } from '@angular/material/snack-bar';

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
  constructor(private apiService: ApiService, private cdr: ChangeDetectorRef, private router: Router,
     private dialog: MatDialog, private snackBar: MatSnackBar) { }
  saveTasks() {
    console.log('Tasks Updated:', this.taskList);
    let user: any;
    user = localStorage.getItem('user');
    this.user = JSON.parse(user);
    // You can send this to API/backend here
  }


  ngOnInit(): void {
    let user: any;
    let postjson: any;
    user = localStorage.getItem('user');
    this.user = JSON.parse(user);
    console.log("this.user", this.user);
    if (this.user.role_name == "Admin") {
      this.fetchTaskAdmin();
    } else {
      this.fetchTaskTech();
    }

  }

  fetchTaskAdmin() {
    let postjson = {
        "mode": "fetch_admin"
      }
    console.log('fetch by admin', postjson);
    this.apiService.post<any[]>('assign_technician.php', postjson).subscribe((res: any) => {
      console.log('this.enqData', res);
      if (res && res.data.length > 0) {
         this.taskList = res.data;
        console.log('this.taskList', this.taskList);
        setTimeout(() => {
          //  this.taskColumns = Object.keys(this.taskList[0]);
          this.taskColumns = [...res.columns, 'Actions'];
          console.log('this.taskColumns', this.taskColumns);
          //  this.taskColumns.push('Completed');   // 🔑 Extract column names
          this.cdr.detectChanges();
        });
      } else {
        this.taskList = [];
        this.taskColumns = res.columns;
        console.log('this.taskList', this.taskList);
        console.log('this.taskColumns', this.taskColumns);
        this.cdr.detectChanges();
      }
    });
  }


  fetchTaskTech() {
    let postjson = {
        "mode": "fetch_by_technician",
        "technician_employee_id": this.user.employee_number
      };
    console.log('fetch by Tech', postjson);
    this.apiService.post<any[]>('assign_technician.php', postjson).subscribe((res: any) => {
      console.log('this.enqData', res);
      if (res && res.data.length > 0) {
        //  this.taskList = res.data;
        this.taskList = res.data.map((task: any) => ({
          ...task,
          technicians: task.technicians.map((t: any) => t.employee_name).join(', ')
        }));
        console.log('this.taskList', this.taskList);
        setTimeout(() => {
          //  this.taskColumns = Object.keys(this.taskList[0]);
          this.taskColumns = [...res.columns, 'Actions'];
          console.log('this.taskColumns', this.taskColumns);
          //  this.taskColumns.push('Completed');   // 🔑 Extract column names
          this.cdr.detectChanges();
        });
      } else {
        this.taskList = [];
        this.taskColumns = ["enquiry_id", "client_name", "contact_person_name", "contact_no1", "enquiry_date", "status_name", "technician_name"];
        console.log('this.taskList', this.taskList);
        console.log('this.taskColumns', this.taskColumns);
        this.cdr.detectChanges();
      }
    });
  }

  onViewtask(task: any) {
     this.router.navigate(['/task-view'], { state: { enquiryId: task.enquiry_id, taskId: task.assignment_id } });
  }

  onEdittask(task: any) {
    console.log('row value', task);
    this.router.navigate(['/task-view'], { state: { enquiryId: task.enquiry_id, taskId: task.assignment_id } });
    // this.router.navigate(['/task-view']);
  }

  onDeletetask(task: any){
     console.log('row value onDeletetask', task);
      const dialogRef = this.dialog.open(ConfirmDialogComponent, {
              width: '350px',
              data: { message: 'Are you sure you want to delete this task?' }
            });
      
            dialogRef.afterClosed().subscribe(result => {
              if (result) {
                this.deleteTask(task);
              }
            });
  }


deleteTask(task: any){
 this.apiService.post<any[]>('assignment_delete.php', {assignment_id: task.assignment_id}).subscribe((res: any) => {
  this.snackBar.open(res.message, 'Close', {
            duration: 3000,
            verticalPosition: 'top',
            horizontalPosition: 'right',
          });
         
//  this.router.navigate(['/task-list']);
const currentUrl = this.router.url;
  this.router.navigateByUrl('/', { skipLocationChange: true }).then(() => {
    this.router.navigate([currentUrl]);
  });
   this.cdr.detectChanges();
 })
}
}
