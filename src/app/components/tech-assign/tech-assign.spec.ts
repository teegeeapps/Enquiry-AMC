import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TechAssign } from './tech-assign';

describe('TechAssign', () => {
  let component: TechAssign;
  let fixture: ComponentFixture<TechAssign>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [TechAssign]
    })
    .compileComponents();

    fixture = TestBed.createComponent(TechAssign);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
