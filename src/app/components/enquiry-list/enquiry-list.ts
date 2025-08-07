import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { ApiService } from '../../services/api-service';

@Component({
  selector: 'app-enquiry-list',
  standalone: false,
  templateUrl: './enquiry-list.html',
  styleUrl: './enquiry-list.scss'
})
export class EnquiryListComponent implements OnInit {

  allData: any[] = [];
  enquiryData: any[] = [];
  enquiryColumns: string[] = [];
  displayedColumns: string[] = [];

  selectedMonth = '';
  selectedYear = '';
  searchText = '';

  months = [
    { label: 'January', value: '01' },
    { label: 'February', value: '02' },
    { label: 'March', value: '03' },
    { label: 'April', value: '04' },
    { label: 'May', value: '05' },
    { label: 'June', value: '06' },
    { label: 'July', value: '07' },
    { label: 'August', value: '08' },
    { label: 'September', value: '09' },
    { label: 'October', value: '10' },
    { label: 'November', value: '11' },
    { label: 'December', value: '12' }
  ];

  years = Array.from({ length: 10 }, (_, i) => new Date().getFullYear() - i);

  constructor(private apiService: ApiService, private cdr: ChangeDetectorRef, private router: Router) { }

  ngOnInit(): void {
    this.apiService.get<any[]>('get_enquiry_list.php').subscribe((res: any) => {
      console.log('this.enqData', res);
      if (res && res.data.length > 0) {
        this.enquiryData = res.data;
        console.log('this.enqData', this.enquiryData);
        setTimeout(() => {
         // this.enquiryColumns = Object.keys(this.enquiryData[0]);
         // this.enquiryColumns.push('Actions');   // 🔑 Extract column names
       //  this.enquiryColumns = ['sno', ...res.columns];
          this.displayedColumns = [ ...res.columns, 'Actions'];
          console.log('this.displayedColumns', this.displayedColumns);
          this.cdr.detectChanges();
        });

      } else {
        this.enquiryData = [];
        this.displayedColumns = ['client_name', 'contact_person_name', 'contact_no1', 'requirement_category', 'enquiry_date', 'enquiry_status_id', 'technician_name', 'status_name'];
        console.log('this.taskList', this.enquiryData);
        console.log('this.taskColumns', this.displayedColumns);
        this.cdr.detectChanges();
      }
    });
  }

  applyFilter(): void {
    this.enquiryData = this.allData
      .filter(item => {
        const deliveryDate = new Date(item.requested_delivery_date);
        const matchMonth = this.selectedMonth ? (('0' + (deliveryDate.getMonth() + 1)).slice(-2) === this.selectedMonth) : true;
        const matchYear = this.selectedYear ? (deliveryDate.getFullYear().toString() === this.selectedYear) : true;
        const matchClient = item.client_name.toLowerCase().includes(this.searchText.toLowerCase());

        return matchMonth && matchYear && matchClient;
      })
      .map((item, index) => ({
        ...item,
        sno: index + 1,
        requested_delivery_date: item.requested_delivery_date ? new Date(item.requested_delivery_date).toLocaleDateString() : '',
        amc_date: item.amc_date ? new Date(item.amc_date).toLocaleDateString() : '-'
      }));
  }

  onViewEnquiry(enquiry: any) {
    this.router.navigate(['/employee-view'], { state: { enquiryId: enquiry.id } });
  }

  onEditEnquiry(enquiry: any) {
    console.log('row value', enquiry);
    this.router.navigate(['/create-enquiry'], { state: { enquiryId: enquiry.enquiry_id } });
  }

  onAmcUpdate(row: any) {
    console.log('AMC clicked for:', row);
    this.router.navigate(['/amc-list'], { state: { enquiryId: row.enquiry_id } });
    // open dialog or handle AMC update here
  }

  addNewEnquiry() {
    this.router.navigate(['/create-enquiry']);
  }

  onAssignTechnician(enquiry: any) {
    console.log('enquiry', enquiry);
    this.router.navigate(['/tech-assign'], { state: { enquiryId: enquiry.enquiry_id } });
  }

  getSerialNumber(index: number): number {
  return index + 1;
}


}