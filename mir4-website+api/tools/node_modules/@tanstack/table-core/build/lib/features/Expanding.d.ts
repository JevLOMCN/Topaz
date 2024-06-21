import { RowModel } from '..';
import { TableFeature } from '../core/table';
import { OnChangeFn, Table, Row, Updater, RowData } from '../types';
export type ExpandedStateList = Record<string, boolean>;
export type ExpandedState = true | Record<string, boolean>;
export interface ExpandedTableState {
    expanded: ExpandedState;
}
export interface ExpandedRow {
    toggleExpanded: (expanded?: boolean) => void;
    getIsExpanded: () => boolean;
    getCanExpand: () => boolean;
    getToggleExpandedHandler: () => () => void;
}
export interface ExpandedOptions<TData extends RowData> {
    manualExpanding?: boolean;
    onExpandedChange?: OnChangeFn<ExpandedState>;
    autoResetExpanded?: boolean;
    enableExpanding?: boolean;
    getExpandedRowModel?: (table: Table<any>) => () => RowModel<any>;
    getIsRowExpanded?: (row: Row<TData>) => boolean;
    getRowCanExpand?: (row: Row<TData>) => boolean;
    paginateExpandedRows?: boolean;
}
export interface ExpandedInstance<TData extends RowData> {
    _autoResetExpanded: () => void;
    setExpanded: (updater: Updater<ExpandedState>) => void;
    toggleAllRowsExpanded: (expanded?: boolean) => void;
    resetExpanded: (defaultState?: boolean) => void;
    getCanSomeRowsExpand: () => boolean;
    getToggleAllRowsExpandedHandler: () => (event: unknown) => void;
    getIsSomeRowsExpanded: () => boolean;
    getIsAllRowsExpanded: () => boolean;
    getExpandedDepth: () => number;
    getExpandedRowModel: () => RowModel<TData>;
    _getExpandedRowModel?: () => RowModel<TData>;
    getPreExpandedRowModel: () => RowModel<TData>;
}
export declare const Expanding: TableFeature;
