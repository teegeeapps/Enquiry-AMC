import { Component, Input, Output, EventEmitter, ViewChild, AfterViewInit, OnChanges, SimpleChanges, OnInit} from '@angular/core';
import { MatTableDataSource } from '@angular/material/table';
import { MatPaginator } from '@angular/material/paginator';
import { MatSort } from '@angular/material/sort';

@Component({
  selector: 'app-dynamic-table',
  standalone: false,
  templateUrl: './dynamic-table.html',
  styleUrl: './dynamic-table.scss'
})
export class DynamicTableComponent implements OnInit, AfterViewInit, OnChanges{
  @Input() data: any[] = [];
  @Input() columns: string[] = [];
  displayedColumns: string[] = [];
  @Input() enableFilter: boolean = true;
  @Input() enablePagination: boolean = true;
  @Input() showAssignTechnician: boolean = false;
  @Input() showCompletedDropdown: boolean = false; 
  @Input() showAmcButton: boolean = false; // ✅ default hidden
  @Output() view = new EventEmitter<any>();
  @Output() edit = new EventEmitter<any>();
  @Output() assignTechnician = new EventEmitter<any>();
  @Output() amc = new EventEmitter<any>(); // ✅ emits when AMC button clicked
@Output() completedSave = new EventEmitter<{row: any, value: string}>(); 
  dataSource = new MatTableDataSource<any>();

  @ViewChild(MatPaginator) paginator!: MatPaginator;
  @ViewChild(MatSort) sort!: MatSort;
  // Store selected completion per row
selectedCompletion: { [key: string]: string } = {};

  ngOnInit(): void {
    console.log('Data source:', this.data);
    this.dataSource.data = this.data; // ✅ Initial setup
 //   console.log('Data source length:', this.dataSource.data.length);
    console.log('this.column:', this.columns);
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['data']) {
    this.dataSource.data = this.data;
  }

  if (changes['columns']) {
    // Always prepend S.No column
    this.displayedColumns = ['sno', ...this.columns];
  }

  console.log('this.data:', this.data);
  console.log('this.columns:', this.columns);
  console.log('this.displayedColumns:', this.displayedColumns);
  }

  ngAfterViewInit(): void {
    this.connectDataSource();
  }

  connectDataSource() {
    if (this.enablePagination && this.paginator) {
      this.dataSource.paginator = this.paginator;
    }
    if (this.sort) {
      this.dataSource.sort = this.sort;
    }
  }

  applyFilter(event: Event) {
    const filterValue = (event.target as HTMLInputElement).value.trim().toLowerCase();
    this.dataSource.filter = filterValue;
  }

  onView(row: any): void {
  this.view.emit(row);
}

  onEdit(row: any): void {
    this.edit.emit(row);
  }

  onAmc(row: any) {
  this.amc.emit(row);
}

  
onAssignTechnician(row: any) {
  this.assignTechnician.emit(row);
}

get columnCount(): number {
  return this.columns?.length || 1;
}

onCompletionChange(row: any, value: string) {
  // Store selection using row unique id or index
  this.selectedCompletion[row.id || this.data.indexOf(row)] = value;
}

onSaveCompletion(row: any) {
  const key = row.id || this.data.indexOf(row);
  const value = this.selectedCompletion[key];
  if (value) {
    this.completedSave.emit({ row, value });
  }
}

getSerialNumber(index: number): number {
  if (this.enablePagination && this.paginator) {
    return index + 1 + this.paginator.pageIndex * this.paginator.pageSize;
  }
  return index + 1;
}

}
