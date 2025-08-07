import { Component, Input  } from '@angular/core';

@Component({
  selector: 'app-view-enquiry',
  standalone: false,
  templateUrl: './view-enquiry.html',
  styleUrl: './view-enquiry.scss'
})
export class ViewEnquiryComponent {
  @Input() enquiry: any;
}
