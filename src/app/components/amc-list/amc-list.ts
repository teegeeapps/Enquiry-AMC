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
  filteredData: any[] = [];
  yearList: number[] = [];
  selectedYear: string = '';
  amcColumns: string[] = [];
  selectedStatus: string = '';
   enquiryStatusOptions: any;
  userColumns = ['s.no', 'client_name', 'contact_person_name', 'contact_no', 'requirement_category', 'delivery_date', 'latest_amc_date'];

  constructor(private router: Router, private apiService: ApiService, private cdr: ChangeDetectorRef) {
    //  this.isEditMode = !!this.enquiryId;

  }

  ngOnInit(): void {
    this.apiService.get<any[]>('get_all_amc_list.php').subscribe((res: any) => {      
    if(res && res.data.length > 0){
        this.amcData = res.data;
        this.filteredData = res.data;
         this.generateYearList(res.data);
         this.loadEnquiryStatusOptions();
        console.log('this.amcData', this.amcData);
         this.amcColumns = [ ...res.columns, 'Actions'];
          this.cdr.detectChanges();
        /* setTimeout(() => { }); */
      } else{
         console.log('inside if');
       this.amcColumns = [ ...res.columns, 'Actions'];
        this.cdr.detectChanges();
      }
    });
  }

  generateYearList(data: any[]): void {
  const years = new Set<number>();

  data.forEach(item => {
    const dateStr = item.refilling_date || item.delivery_date;
    if (dateStr) {
      let year: number | null = null;

      // Handle both formats (DD-MM-YYYY or YYYY-MM-DD)
      if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) {
        // Format: DD-MM-YYYY
        const parts = dateStr.split('-');
        year = parseInt(parts[2], 10);
      } else if (/^\d{4}-\d{2}-\d{2}/.test(dateStr)) {
        // Format: YYYY-MM-DD or ISO
        year = new Date(dateStr).getFullYear();
      }

      if (year && !isNaN(year)) {
        years.add(year);
      }
    }
  });

  this.yearList = Array.from(years).sort((a, b) => b - a);
  console.log('Generated year list:', this.yearList);
}


   loadEnquiryStatusOptions() {
    this.apiService.post('get_status_list.php', {"mode": "amc"})
      .subscribe({
        next: (response: any) => {
          console.log('response enq status', response);
          this.enquiryStatusOptions = response.data;
        },
        error: (err) => {
          console.error('Failed to load enquiry statuses', err);
        }
      });
  }

 applyFilters(): void {
  this.filteredData = this.amcData.filter((item:any) => {
    // --- Extract year safely ---
    const rawDate = (item.refilling_date || item.latest_amc_date || item.delivery_date || '').trim();
    let itemYear = '';

    if (rawDate) {
      const normalizedDate = rawDate.replace(/\//g, '-'); // handle both / and -
      if (/^\d{2}-\d{2}-\d{4}$/.test(normalizedDate)) {
        const parts = normalizedDate.split('-');
        itemYear = parts[2]; // 3rd part = year
      }
    }

    // --- Year match check ---
    const selectedYearStr = (this.selectedYear || '').toString().trim();
    const matchesYear = !selectedYearStr || itemYear === selectedYearStr;

    // --- Status match check ---
    const apiStatus = (item.refilling_status || '').toLowerCase().trim();
    console.log('apiStatus', apiStatus);
    const selectedStatus = (this.selectedStatus || '').toLowerCase().trim();
    console.log('selectedStatus', selectedStatus);
    const matchesStatus = !selectedStatus || apiStatus === selectedStatus;

    return matchesYear && matchesStatus;
  });

  // --- Reset to all if both filters cleared ---
  if (!this.selectedYear && !this.selectedStatus) {
    this.filteredData = [...this.amcData];
  }

  console.log('Filtered:', this.filteredData.length, 'of', this.amcData.length);
}





  addNewAMC() {
    this.router.navigate(['/amc-update'], { state: { enquiryId: this.enquiryId, editMode: false } });
    //  this.router.navigate(['/amc-update']);
  }

  editAMC(row: any){
    console.log('editamc', row);
     this.router.navigate(['/amc-update'], { state: { amc_id: row.amc_id, editMode: true } });
  }

   onAssignTech(enquiry: any) {
    console.log('enquiry', enquiry);
    this.router.navigate(['/tech-assign'], { state: { enquiryId: enquiry.enquiry_id, assignType: "REFILLING" } });
  }
}
