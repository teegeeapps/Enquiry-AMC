import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ViewEmployee } from './view-employee';

describe('ViewEmployee', () => {
  let component: ViewEmployee;
  let fixture: ComponentFixture<ViewEmployee>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ViewEmployee]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ViewEmployee);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
