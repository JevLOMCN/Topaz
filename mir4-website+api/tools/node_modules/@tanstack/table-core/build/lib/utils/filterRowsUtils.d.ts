import { Row, RowModel, Table, RowData } from '../types';
export declare function filterRows<TData extends RowData>(rows: Row<TData>[], filterRowImpl: (row: Row<TData>) => any, table: Table<TData>): RowModel<TData>;
export declare function filterRowModelFromLeafs<TData extends RowData>(rowsToFilter: Row<TData>[], filterRow: (row: Row<TData>) => Row<TData>[], table: Table<TData>): RowModel<TData>;
export declare function filterRowModelFromRoot<TData extends RowData>(rowsToFilter: Row<TData>[], filterRow: (row: Row<TData>) => any, table: Table<TData>): RowModel<TData>;
