import { Component, OnInit, ChangeDetectorRef  } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api-service';

@Component({
  selector: 'app-service-list',
  standalone: false,
  templateUrl: './service-list.html',
  styleUrl: './service-list.scss'
})
export class ServiceListComponent {

  userData: any;
  enquiryId: string | null = null;
  isEditMode = false;
  serviceData: any;
  serviceColumns: string[] = [];

  constructor(private router: Router, private apiService: ApiService, private cdr: ChangeDetectorRef) {
   
  }

   ngOnInit(): void {
    this.apiService.post('service_list.php', {"mode": "FETCH_ALL"}).subscribe((res: any) => {
      console.log("res amc", res);        
    if(res && res.data.length > 0){
        this.serviceData = res.data;
        setTimeout(() => {
         this.serviceColumns = [ ...res.columns, 'Actions'];
          this.cdr.detectChanges();
        });
      } else{
         console.log('inside if');
       this.serviceColumns = [ ...res.columns, 'Actions'];
        this.cdr.detectChanges();
      }
    });
  }

   editService(row: any){
    console.log('editService', row);
     this.router.navigate(['/service-update'], { state: { service_Id: row.id, amc_id: row.amc_id } });
  }

   onAssignTech(service: any) {
    console.log('service', service);
    this.router.navigate(['/tech-assign'], { state: { enquiryId: service.enquiry_id, assignType: "SERVICE" } });
  }

}
