import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ServiceUpdate } from './service-update';

describe('ServiceUpdate', () => {
  let component: ServiceUpdate;
  let fixture: ComponentFixture<ServiceUpdate>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ServiceUpdate]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ServiceUpdate);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
