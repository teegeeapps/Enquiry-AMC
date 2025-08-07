import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { EmployeeListComponent } from './components/employee-list/employee-list';
import { AddEmployeeComponent } from './components/add-employee/add-employee';
import { LoginComponent } from './components/login/login';
import { DashboardComponent } from './components/dashboard/dashboard';

import { CreateEnquiryComponent  } from './components/create-enquiry/create-enquiry';
import { EnquiryListComponent } from './components/enquiry-list/enquiry-list';
import { AmcListComponent } from './components/amc-list/amc-list';
import { AmcUpdateComponent } from './components/amc-update/amc-update';
import { TechAssignComponent } from './components/tech-assign/tech-assign';
import { TaskListComponent } from './components/task-list/task-list';
import { AssignRoleComponent } from './components/assign-role/assign-role';
import { ConfigComponent } from './components/config/config';
import { ViewEnquiryComponent } from './components/view-enquiry/view-enquiry';
import { ViewEmployeeComponent } from './components/view-employee/view-employee';

const routes: Routes = [
  { path: 'dashboard', component: DashboardComponent },
  { path: '', redirectTo: '/login', pathMatch: 'full' },
  // other routes
  { path: 'employee-list', component: EmployeeListComponent},
  { path: 'add-employee', component: AddEmployeeComponent }, // optional
  { path: 'login', component: LoginComponent },
  { path: 'create-enquiry', component: CreateEnquiryComponent },
   { path: 'enquiry-list', component: EnquiryListComponent },
  { path: 'amc-list', component: AmcListComponent },
  { path: 'amc-update', component: AmcUpdateComponent },
  { path: 'tech-assign', component: TechAssignComponent },
  { path: 'task-list', component: TaskListComponent },
   { path: 'assign-role', component: AssignRoleComponent },
   { path: 'config', component: ConfigComponent },
    { path: 'view-employee', component: ViewEmployeeComponent },
   { path: 'view-enquiry', component: ViewEnquiryComponent },
  { path: '**', redirectTo: '/dashboard' } // fallback
];


@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
