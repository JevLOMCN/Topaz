import { Column, Table, AccessorFn, ColumnDef, RowData } from '../types';
export interface CoreColumn<TData extends RowData, TValue> {
    id: string;
    depth: number;
    accessorFn?: AccessorFn<TData, TValue>;
    columnDef: ColumnDef<TData, TValue>;
    columns: Column<TData, TValue>[];
    parent?: Column<TData, TValue>;
    getFlatColumns: () => Column<TData, TValue>[];
    getLeafColumns: () => Column<TData, TValue>[];
}
export declare function createColumn<TData extends RowData, TValue>(table: Table<TData>, columnDef: ColumnDef<TData, TValue>, depth: number, parent?: Column<TData, TValue>): Column<TData, TValue>;
