import { OnChangeFn, Updater, Column, RowData } from '../types';
import { TableFeature } from '../core/table';
export interface ColumnOrderTableState {
    columnOrder: ColumnOrderState;
}
export type ColumnOrderState = string[];
export interface ColumnOrderOptions {
    onColumnOrderChange?: OnChangeFn<ColumnOrderState>;
}
export interface ColumnOrderDefaultOptions {
    onColumnOrderChange: OnChangeFn<ColumnOrderState>;
}
export interface ColumnOrderInstance<TData extends RowData> {
    setColumnOrder: (updater: Updater<ColumnOrderState>) => void;
    resetColumnOrder: (defaultState?: boolean) => void;
    _getOrderColumnsFn: () => (columns: Column<TData, unknown>[]) => Column<TData, unknown>[];
}
export declare const Ordering: TableFeature;
