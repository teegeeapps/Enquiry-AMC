import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';
import { UserService } from '../services/user-service/user-service';

@Injectable({
  providedIn: 'root'
})
export class AuthGuard implements CanActivate {
  userRole: any;

  constructor(private router: Router, private userService: UserService) {}

  canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): boolean {
    // 🔹 1. Check if user data exists in localStorage
    const userData = localStorage.getItem('user');
     this.userService.userRole$.subscribe(role => {
      this.userRole = role;
    });
    if (!this.userRole) {
      console.log('⛔ No user found, redirecting to login');
      this.router.navigate(['/login']);
      return false;
    }

    /* // 🔹 2. Parse user info
    let user: any;
    try {
      user = JSON.parse(userData);
    } catch (e) {
      console.error('Invalid user data in storage');
      localStorage.removeItem('user');
      this.router.navigate(['/login']);
      return false;
    }
 */
   // const role = user.role_name?.toLowerCase();
   /*  if (!this.userRole) {
      console.log('⛔ No role found, redirecting to login');
      localStorage.removeItem('user');
      this.router.navigate(['/login']);
      return false;
    } */

    // 🔹 3. Define allowed routes by role
    const adminRoutes = [
      '/enquiry-list', '/employee-list', '/add-employee', '/assign-role',
      '/config', '/amc-list', '/amc-update', '/service-list', '/service-update', '/create-enquiry', '/tech-assign'
    ];
    const techRoutes = [
      '/task-list', '/task-view'
    ];

    // 🔹 4. Role-based route checks
    const currentUrl = state.url.split('?')[0]; // remove query params
  //  console.log('🔍 Checking access for', role, 'on', currentUrl);

    if (this.userRole === 'Admin') {
      if (!adminRoutes.includes(currentUrl)) {
        console.log('🔁 Admin trying to access technician route → redirecting to enquiry-list');
        this.router.navigate(['/enquiry-list']);
        return false;
      }
      return true;
    }

    if (this.userRole === 'Technician') {
      if (!techRoutes.includes(currentUrl)) {
        console.log('🔁 Technician trying to access admin route → redirecting to task-list');
        this.router.navigate(['/task-list']);
        return false;
      }
      return true;
    }

    // 🔹 4. Handle unknown or invalid routes
    const allKnownRoutes = [...adminRoutes, ...techRoutes, '/login'];
    if (!allKnownRoutes.includes(currentUrl)) {
      console.log('⚠️ Unknown route detected:', currentUrl);

      if (this.userRole === 'Admin') {
        this.router.navigate(['/enquiry-list']);
      } else if (this.userRole === 'Technician') {
        this.router.navigate(['/task-list']);
      } else {
        this.router.navigate(['/login']);
      }
      return false;
    }

    // 🔹 5. If unknown role, clear and redirect
    console.log('❌ Unknown role, redirecting to login');
    localStorage.removeItem('user');
    this.router.navigate(['/login']);
    return false;
  }
}
