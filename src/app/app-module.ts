import { NgModule, provideBrowserGlobalErrorListeners, provideZonelessChangeDetection } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { CommonModule } from '@angular/common';
import { AppRoutingModule } from './app-routing-module';
import { AppComponent } from './app';
import { EmployeeListComponent } from './components/employee-list/employee-list';
import { MatTableModule } from '@angular/material/table';
import { MatInputModule } from '@angular/material/input';
import { MatIconModule } from '@angular/material/icon';
import { MatButtonModule } from '@angular/material/button';
import { MatPaginatorModule } from '@angular/material/paginator';
import { MatSortModule } from '@angular/material/sort';
import { MatFormFieldModule } from '@angular/material/form-field';
import { provideHttpClient, withFetch  } from '@angular/common/http';
import { DynamicTableComponent } from './shared/dynamic-table/dynamic-table';
import { MatSidenavModule } from "@angular/material/sidenav";
import { MatTooltipModule } from "@angular/material/tooltip";
import { MatListModule } from "@angular/material/list";
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatCardModule } from '@angular/material/card';
import { AddEmployeeComponent } from './components/add-employee/add-employee';
import { ReactiveFormsModule, FormsModule} from '@angular/forms';
import { RouterModule  } from '@angular/router';
import { CreateEnquiryComponent } from './components/create-enquiry/create-enquiry';
import { EnquiryListComponent } from './components/enquiry-list/enquiry-list';
import { AmcListComponent } from './components/amc-list/amc-list';
import { AmcUpdateComponent } from './components/amc-update/amc-update';
import { DashboardComponent } from './components/dashboard/dashboard';
import { LoginComponent } from './components/login/login';
import { TaskListComponent } from './components/task-list/task-list';
import { TechAssignComponent } from './components/tech-assign/tech-assign';
import { AssignRoleComponent } from './components/assign-role/assign-role';
import { ConfigComponent } from './components/config/config';
import { ConfirmDialogComponent } from './components/confirm-dialog/confirm-dialog';
import { MatDialogModule } from '@angular/material/dialog';
import { ViewEnquiryComponent } from './components/view-enquiry/view-enquiry';
import { ViewEmployeeComponent } from './components/view-employee/view-employee';
import { FormatHeaderPipe } from './pipes/format-header-pipe';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { MatMenuModule } from '@angular/material/menu';
import { MatSelectModule } from '@angular/material/select';
import { MatExpansionModule } from '@angular/material/expansion';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatNativeDateModule } from '@angular/material/core';
import { MatChipsModule } from '@angular/material/chips';



@NgModule({
  declarations: [
    AppComponent,
    EmployeeListComponent,
    DynamicTableComponent,
    AddEmployeeComponent,
    CreateEnquiryComponent,
    EnquiryListComponent,
    AmcListComponent,
    AmcUpdateComponent,
    DashboardComponent,
    LoginComponent,
    TaskListComponent,
    TechAssignComponent,
    AssignRoleComponent,
    ConfigComponent,
    ConfirmDialogComponent,
    ViewEnquiryComponent,
    ViewEmployeeComponent,
    FormatHeaderPipe
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    CommonModule,
    MatTableModule,
    MatInputModule,
    MatIconModule,
    MatButtonModule,
    MatPaginatorModule,
    MatSortModule,
    MatFormFieldModule,
    MatSidenavModule,
    MatTooltipModule,
    MatListModule,
    MatToolbarModule,
    MatCardModule,
    ReactiveFormsModule,
    FormsModule,
    RouterModule,
    MatDialogModule,
    MatSnackBarModule,
    MatMenuModule,
    MatSelectModule,
    MatExpansionModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatChipsModule
  ],
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideZonelessChangeDetection(),
    provideHttpClient(),
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
