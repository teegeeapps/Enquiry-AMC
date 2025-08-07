import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ViewEnquiry } from './view-enquiry';

describe('ViewEnquiry', () => {
  let component: ViewEnquiry;
  let fixture: ComponentFixture<ViewEnquiry>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ViewEnquiry]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ViewEnquiry);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
