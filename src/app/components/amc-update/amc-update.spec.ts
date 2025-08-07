import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AmcUpdate } from './amc-update';

describe('AmcUpdate', () => {
  let component: AmcUpdate;
  let fixture: ComponentFixture<AmcUpdate>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [AmcUpdate]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AmcUpdate);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
