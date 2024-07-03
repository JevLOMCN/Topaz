import { TableFeature } from '../core/table';
import { OnChangeFn, Updater, Column, Cell, RowData } from '../types';
export type ColumnPinningPosition = false | 'left' | 'right';
export interface ColumnPinningState {
    left?: string[];
    right?: string[];
}
export interface ColumnPinningTableState {
    columnPinning: ColumnPinningState;
}
export interface ColumnPinningOptions {
    onColumnPinningChange?: OnChangeFn<ColumnPinningState>;
    enablePinning?: boolean;
}
export interface ColumnPinningDefaultOptions {
    onColumnPinningChange: OnChangeFn<ColumnPinningState>;
}
export interface ColumnPinningColumnDef {
    enablePinning?: boolean;
}
export interface ColumnPinningColumn {
    getCanPin: () => boolean;
    getPinnedIndex: () => number;
    getIsPinned: () => ColumnPinningPosition;
    pin: (position: ColumnPinningPosition) => void;
}
export interface ColumnPinningRow<TData extends RowData> {
    getLeftVisibleCells: () => Cell<TData, unknown>[];
    getCenterVisibleCells: () => Cell<TData, unknown>[];
    getRightVisibleCells: () => Cell<TData, unknown>[];
}
export interface ColumnPinningInstance<TData extends RowData> {
    setColumnPinning: (updater: Updater<ColumnPinningState>) => void;
    resetColumnPinning: (defaultState?: boolean) => void;
    getIsSomeColumnsPinned: (position?: ColumnPinningPosition) => boolean;
    getLeftLeafColumns: () => Column<TData, unknown>[];
    getRightLeafColumns: () => Column<TData, unknown>[];
    getCenterLeafColumns: () => Column<TData, unknown>[];
}
export declare const Pinning: TableFeature;
