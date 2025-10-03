import { Injectable } from '@angular/core';
import { NativeDateAdapter } from '@angular/material/core';

@Injectable()
export class IstDateAdapter extends NativeDateAdapter {
  // Always create date at midnight IST
  override createDate(year: number, month: number, date: number): Date {
    const result = new Date(year, month, date);
    result.setHours(0, 0, 0, 0); // remove time part
    return result;
  }

  // Ensure deserialization also strips time properly
  override deserialize(value: any): Date | null {
    if (typeof value === 'string' || typeof value === 'number') {
      const date = new Date(value);
      date.setHours(0, 0, 0, 0);
      return date;
    }
    return super.deserialize(value);
  }
}
