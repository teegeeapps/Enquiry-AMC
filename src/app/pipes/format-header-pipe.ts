import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'formatHeader',
  standalone: false
})
export class FormatHeaderPipe implements PipeTransform {
  transform(value: string): string {
    if (!value) return '';

    return value
      .replace(/_/g, ' ') // Replace underscores with spaces
      .split(' ')         // Split into words
      .map(word => word.charAt(0).toUpperCase() + word.substring(1).toLowerCase())
      .join(' ');         // Rejoin with spaces
  }
}