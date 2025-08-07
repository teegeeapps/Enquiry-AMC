import { Injectable } from '@angular/core';
import { BehaviorSubject } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class UserService {

  private usernameSubject = new BehaviorSubject<string | null>(null);
  public username$ = this.usernameSubject.asObservable();

   private userRoleSubject = new BehaviorSubject<string | null>(null);
  public userRole$ = this.userRoleSubject.asObservable();

  constructor() {
    // Initialize from localStorage (if exists)
    const user = localStorage.getItem('user');
    if (user) {
      const parsedUser = JSON.parse(user);
      this.usernameSubject.next(parsedUser?.employee_name || null);
      this.userRoleSubject.next(parsedUser?.role_name || null);
    }
  }

  setUsername(username: string) {
    this.usernameSubject.next(username);
  }

  getUsername(): string | null {
    return this.usernameSubject.value;
  }

  clear() {
    this.usernameSubject.next(null);
  }

   setRole(role: string) {
    this.userRoleSubject.next(role);
  }

  getRole(): string | null {
    return this.userRoleSubject.value;
  }
}
