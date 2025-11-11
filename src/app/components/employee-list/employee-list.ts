import { Component, OnInit, ChangeDetectorRef   } from '@angular/core';
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
  filteredEmployeeData: any[] = [];
  employeeColumns: string[] = [];
  selectedStatus: string = 'Active'; // ✅ Default: Active employees
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
        // ✅ Apply initial filter (Active by default)
        this.applyStatusFilter();
          this.cdr.detectChanges();
    //  });
       
      } else {
        this.employeeColumns = [ ...res.columns, 'Actions'];
      }
    });
  }

   // ✅ Status filter logic
applyStatusFilter(): void {
  console.log("inside applyStatusFilter");
  const selected = (this.selectedStatus || '').toLowerCase().trim();
 console.log("inside applyStatusFilter selected", selected);
  if (!selected) {
    // ✅ If "All Status" selected, show all employees
    this.filteredEmployeeData = [...this.employeeData];
    console.log("inside if", this.filteredEmployeeData);
  } else {
    this.filteredEmployeeData = this.employeeData.filter(emp =>
      emp.status.toLowerCase().trim() === selected
    );
     console.log("inside else", this.filteredEmployeeData);
  }
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
