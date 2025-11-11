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
import { MatCheckboxModule } from '@angular/material/checkbox';
import { TaskViewComponent } from './components/task-view/task-view';
import { MatMomentDateModule, MomentDateAdapter } from '@angular/material-moment-adapter';
import { DateAdapter, MAT_DATE_FORMATS, MAT_DATE_LOCALE } from '@angular/material/core';
import * as _moment from 'moment';
import { ServiceListComponent } from './components/service-list/service-list';
import { ServiceUpdateComponent } from './components/service-update/service-update';
import { IstDateAdapter } from './shared/ist-date-adapter'; // adjust path if needed



const moment = _moment;

export const MY_FORMATS = {
  parse: {
    dateInput: 'DD-MM-YYYY',
  },
  display: {
    dateInput: 'DD-MM-YYYY',
    monthYearLabel: 'MMM YYYY',
    dateA11yLabel: 'DD-MM-YYYY',
    monthYearA11yLabel: 'MMMM YYYY',
  },
};

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
    LoginComponent,
    TaskListComponent,
    TechAssignComponent,
    AssignRoleComponent,
    ConfigComponent,
    ConfirmDialogComponent,
    ViewEnquiryComponent,
    ViewEmployeeComponent,
    FormatHeaderPipe,
    TaskViewComponent,
    ServiceListComponent,
    ServiceUpdateComponent,


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
    MatChipsModule,
    MatCheckboxModule,
     MatMomentDateModule
  ],
  providers: [
    provideBrowserGlobalErrorListeners(),
    provideZonelessChangeDetection(),
    provideHttpClient(),
    { provide: DateAdapter, useClass: IstDateAdapter },
    { provide: MAT_DATE_FORMATS, useValue: MY_FORMATS },
    { provide: MAT_DATE_LOCALE, useValue: 'en-GB' },
  ],
  bootstrap: [AppComponent]
})
export class AppModule { }



