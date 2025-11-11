import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { EmployeeListComponent } from './components/employee-list/employee-list';
import { AddEmployeeComponent } from './components/add-employee/add-employee';
import { LoginComponent } from './components/login/login';

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
import { TaskViewComponent } from './components/task-view/task-view';
import { ServiceListComponent } from './components/service-list/service-list';
import { ServiceUpdateComponent } from './components/service-update/service-update';
import { AuthGuard } from './guards/auth-guard';


const routes: Routes = [
  { path: '', redirectTo: '/login', pathMatch: 'full' },
   { path: 'login', component: LoginComponent },
  // other routes
  { path: 'employee-list', component: EmployeeListComponent, canActivate: [AuthGuard]},
  { path: 'add-employee', component: AddEmployeeComponent, canActivate: [AuthGuard] }, // optional
 
  { path: 'create-enquiry', component: CreateEnquiryComponent, canActivate: [AuthGuard] },
   { path: 'enquiry-list', component: EnquiryListComponent, canActivate: [AuthGuard]  },
  { path: 'amc-list', component: AmcListComponent, canActivate: [AuthGuard] },
  { path: 'amc-update', component: AmcUpdateComponent, canActivate: [AuthGuard] },
  { path: 'tech-assign', component: TechAssignComponent,canActivate: [AuthGuard] },
  { path: 'task-list', component: TaskListComponent, canActivate: [AuthGuard] },
   { path: 'assign-role', component: AssignRoleComponent, canActivate: [AuthGuard] },
   { path: 'config', component: ConfigComponent, canActivate: [AuthGuard] },
    { path: 'view-employee', component: ViewEmployeeComponent, canActivate: [AuthGuard] },
   { path: 'view-enquiry', component: ViewEnquiryComponent, canActivate: [AuthGuard] },
   { path: 'task-view', component: TaskViewComponent, canActivate: [AuthGuard] },
   { path: 'service-list', component: ServiceListComponent, canActivate: [AuthGuard] },
   { path: 'service-update', component: ServiceUpdateComponent, canActivate: [AuthGuard] },

   { path: '**', canActivate: [AuthGuard], component: LoginComponent }
// { path: '**', redirectTo: '/login' }
];


@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
