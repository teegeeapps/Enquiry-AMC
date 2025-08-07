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

  constructor(private router: Router, private apiService: ApiService, private cdr: ChangeDetectorRef,) {
    const nav = this.router.getCurrentNavigation();
    const state = nav?.extras?.state as { enquiryId?: string };
    this.enquiryId = state?.enquiryId || null;
    //  this.isEditMode = !!this.enquiryId;

  }

  ngOnInit(): void {
    this.apiService.post<any[]>('get_amc_list.php', {"enquiry_id": this.enquiryId}).subscribe((res: any) => {
      console.log("res", res);        
    if(res && res.amc.length > 0){
         console.log('inside else if');
        this.amcData = res.amc;
        console.log('this.amcData', this.amcData);
        setTimeout(() => {
          this.amcColumns = Object.keys(this.amcData[0]);
          this.amcColumns.push('Actions');   // 🔑 Extract column names
          console.log('this.enqData', this.amcColumns);
          this.cdr.detectChanges();
        });
      } else{
         console.log('inside if');
        this.amcData = [];
        this.amcColumns = ['s.no', 'client_name', 'contact_person_name', 'contact_no', 'requirement_category', 'delivery_date', 'latest_amc_date'];
        console.log('this.taskList', this.amcColumns);
        console.log('this.taskColumns', this.amcColumns);
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
     this.router.navigate(['/amc-update'], { state: { enquiryId: this.enquiryId, editMode: true } });
  }
}
