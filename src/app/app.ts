import { Component, ViewChild, OnInit } from '@angular/core';
import { MatSidenav } from '@angular/material/sidenav';
import { Router } from '@angular/router';
import { ApiService } from './services/api-service';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogComponent } from './components/confirm-dialog/confirm-dialog';
import { UserService } from './services/user-service/user-service';

@Component({
  selector: 'app-root',
  templateUrl: './app.html',
  standalone: false,
  styleUrl: './app.scss'
})
export class AppComponent implements OnInit{
  protected title = 'AMC-Enq';
  userRole: any;
  username: string | null = null;
  /* public isExpanded = false;

  public toggleMenu() {
    this.isExpanded = !this.isExpanded;
  } */
  @ViewChild('sidenav') sidenav!: MatSidenav;
  user: any;
  constructor(public router: Router, private apiService: ApiService, private dialog: MatDialog, private userService: UserService) {
    
  }

  ngOnInit(): void {
    let user: any;
    user = localStorage.getItem('user');
    this.user = JSON.parse(user);
     this.userService.username$.subscribe(name => {
      this.username = name;
    });
    this.userService.userRole$.subscribe(role => {
      this.userRole = role;
    });
 //   this.userRole = this.user?.role_name;
   // this.username = this.user?.employee_name || 'User';
    console.log('employee_data username', this.username);
    console.log('employee_data userRole', this.userRole);
  }

  toggleSidebar() {
    this.sidenav.toggle();
  }

  onSubmit() {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      width: '350px',
      data: { message: 'Are you sure you want to Logout?' }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.logout();
      }
    });

  }

  logout() {
    localStorage.removeItem('user');
    localStorage.clear();
    this.router.navigate(['/login']);
  }
}
