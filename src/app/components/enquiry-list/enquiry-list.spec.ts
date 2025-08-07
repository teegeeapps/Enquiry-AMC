import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EnquiryList } from './enquiry-list';

describe('EnquiryList', () => {
  let component: EnquiryList;
  let fixture: ComponentFixture<EnquiryList>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [EnquiryList]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EnquiryList);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
