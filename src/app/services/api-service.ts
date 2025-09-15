import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})

export class ApiService {
//private baseUrl = 'http://localhost/enquiry-amc-backend'; // 🔁 Your backend base URL
private baseUrl = 'https://tgapp.infy.uk/enquiry_amc_backend'; // 🔁 Your backend base URL
// private baseUrl = 'http://localhost/enquiry-amc/enquiry-amc-backend';

  constructor(private http: HttpClient) {}

  // ✅ GET request
  get<T>(endpoint: string, params?: HttpParams): Observable<T> {
    return this.http.get<T>(`${this.baseUrl}/${endpoint}`, { params });
  }

  // ✅ POST request
  post<T>(endpoint: string, body: any, headers?: HttpHeaders): Observable<T> {
    return this.http.post<T>(`${this.baseUrl}/${endpoint}`, body, {
      headers,
    });
  }
}