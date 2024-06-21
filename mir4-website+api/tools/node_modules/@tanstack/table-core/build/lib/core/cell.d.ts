import { RowData, Cell, Column, Row, Table } from '../types';
import { Getter } from '../utils';
export interface CellContext<TData extends RowData, TValue> {
    table: Table<TData>;
    column: Column<TData, TValue>;
    row: Row<TData>;
    cell: Cell<TData, TValue>;
    getValue: Getter<TValue>;
    renderValue: Getter<TValue | null>;
}
export interface CoreCell<TData extends RowData, TValue> {
    id: string;
    getValue: CellContext<TData, TValue>['getValue'];
    renderValue: CellContext<TData, TValue>['renderValue'];
    row: Row<TData>;
    column: Column<TData, TValue>;
    getContext: () => CellContext<TData, TValue>;
}
export declare function createCell<TData extends RowData, TValue>(table: Table<TData>, row: Row<TData>, column: Column<TData, TValue>, columnId: string): Cell<TData, TValue>;
