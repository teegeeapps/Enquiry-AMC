import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AssignRole } from './assign-role';

describe('AssignRole', () => {
  let component: AssignRole;
  let fixture: ComponentFixture<AssignRole>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [AssignRole]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AssignRole);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
