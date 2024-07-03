import { Table, RowModel, RowData } from '../types';
export declare function getGroupedRowModel<TData extends RowData>(): (table: Table<TData>) => () => RowModel<TData>;
