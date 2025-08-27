import { Component, OnInit, ChangeDetectorRef   } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api-service';

@Component({
  selector: 'app-employee-list',
  standalone: false,
  templateUrl: './employee-list.html',
  styleUrl: './employee-list.scss'
})
export class EmployeeListComponent implements OnInit{
  employeeData: any[] = [];
  employeeColumns: string[] = [];

constructor(private apiService: ApiService, private cdr: ChangeDetectorRef, private router: Router) {}

  ngOnInit(): void {
    this.apiService.get<any[]>('get_employees_list.php').subscribe((res: any) => {
      console.log('get employee', res);
        if (res && res.data.length > 0) {
        //  this.employeeData = res.data;
          this.employeeData = res.data.map((emp: any) => ({
                  ...emp,
                  status: emp.status === "1" ? "Active" : "In Active"// true if "1", false if "0"
              }));
          console.log('this.employeeData', this.employeeData);
       // setTimeout(() => {
       // this.employeeColumns = Object.keys(this.employeeData[0]);
      //  this.employeeColumns.push('Actions');   // 🔑 Extract column names
        this.employeeColumns = [ ...res.columns, 'Actions'];
        console.log('this.employeeData', this.employeeColumns );
          this.cdr.detectChanges();
    //  });
       
      } else {
        this.employeeColumns = [ ...res.columns, 'Actions'];
      }
    });
  }

   addNewEmployee() {
    this.router.navigate(['/add-employee']);
  }

  onViewEmployee(employee: any) {
  this.router.navigate(['/employee-view'], { state: { employeeId: employee.id } });
}

  onEditEmployee(employee: any) {
    console.log('row value', employee);
    console.log('emp number', employee.employee_number);
    this.router.navigate(['/add-employee'], { state: { employeeId: employee.employee_number } });
  }

}
