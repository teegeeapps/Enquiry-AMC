import { Component, OnInit, ChangeDetectorRef  } from '@angular/core';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api-service';

@Component({
  selector: 'app-amc-list',
  standalone: false,
  templateUrl: './amc-list.html',
  styleUrl: './amc-list.scss'
})
export class AmcListComponent implements OnInit{
  userData: any;
  enquiryId: string | null = null;
  isEditMode = false;
  amcData: any;
  amcColumns: string[] = [];
  userColumns = ['s.no', 'client_name', 'contact_person_name', 'contact_no', 'requirement_category', 'delivery_date', 'latest_amc_date'];

  constructor(private router: Router, private apiService: ApiService, private cdr: ChangeDetectorRef) {
    //  this.isEditMode = !!this.enquiryId;

  }

  ngOnInit(): void {
    this.apiService.get<any[]>('get_all_amc_list.php').subscribe((res: any) => {      
    if(res && res.data.length > 0){
        this.amcData = res.data;
        console.log('this.amcData', this.amcData);
        setTimeout(() => {
         this.amcColumns = [ ...res.columns, 'Actions'];
          this.cdr.detectChanges();
        });
      } else{
         console.log('inside if');
       this.amcColumns = [ ...res.columns, 'Actions'];
        this.cdr.detectChanges();
      }
    });
  }
  addNewAMC() {
    this.router.navigate(['/amc-update'], { state: { enquiryId: this.enquiryId, editMode: false } });
    //  this.router.navigate(['/amc-update']);
  }

  editAMC(row: any){
    console.log('editamc', row);
     this.router.navigate(['/amc-update'], { state: { enquiryId: row.enquiry_id, editMode: true } });
  }

   onAssignTech(enquiry: any) {
    console.log('enquiry', enquiry);
    this.router.navigate(['/tech-assign'], { state: { enquiryId: enquiry.enquiry_id, assignType: "REFILLING" } });
  }
}
