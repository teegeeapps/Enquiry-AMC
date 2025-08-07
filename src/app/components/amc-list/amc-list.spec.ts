import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AmcList } from './amc-list';

describe('AmcList', () => {
  let component: AmcList;
  let fixture: ComponentFixture<AmcList>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [AmcList]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AmcList);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
