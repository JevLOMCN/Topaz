import { TableFeature } from '../core/table';
import { OnChangeFn, Table, Row, RowModel, Updater, RowData } from '../types';
export type RowSelectionState = Record<string, boolean>;
export interface RowSelectionTableState {
    rowSelection: RowSelectionState;
}
export interface RowSelectionOptions<TData extends RowData> {
    enableRowSelection?: boolean | ((row: Row<TData>) => boolean);
    enableMultiRowSelection?: boolean | ((row: Row<TData>) => boolean);
    enableSubRowSelection?: boolean | ((row: Row<TData>) => boolean);
    onRowSelectionChange?: OnChangeFn<RowSelectionState>;
}
export interface RowSelectionRow {
    getIsSelected: () => boolean;
    getIsSomeSelected: () => boolean;
    getIsAllSubRowsSelected: () => boolean;
    getCanSelect: () => boolean;
    getCanMultiSelect: () => boolean;
    getCanSelectSubRows: () => boolean;
    toggleSelected: (value?: boolean) => void;
    getToggleSelectedHandler: () => (event: unknown) => void;
}
export interface RowSelectionInstance<TData extends RowData> {
    getToggleAllRowsSelectedHandler: () => (event: unknown) => void;
    getToggleAllPageRowsSelectedHandler: () => (event: unknown) => void;
    setRowSelection: (updater: Updater<RowSelectionState>) => void;
    resetRowSelection: (defaultState?: boolean) => void;
    getIsAllRowsSelected: () => boolean;
    getIsAllPageRowsSelected: () => boolean;
    getIsSomeRowsSelected: () => boolean;
    getIsSomePageRowsSelected: () => boolean;
    toggleAllRowsSelected: (value?: boolean) => void;
    toggleAllPageRowsSelected: (value?: boolean) => void;
    getPreSelectedRowModel: () => RowModel<TData>;
    getSelectedRowModel: () => RowModel<TData>;
    getFilteredSelectedRowModel: () => RowModel<TData>;
    getGroupedSelectedRowModel: () => RowModel<TData>;
}
export declare const RowSelection: TableFeature;
export declare function selectRowsFn<TData extends RowData>(table: Table<TData>, rowModel: RowModel<TData>): RowModel<TData>;
export declare function isRowSelected<TData extends RowData>(row: Row<TData>, selection: Record<string, boolean>): boolean;
export declare function isSubRowSelected<TData extends RowData>(row: Row<TData>, selection: Record<string, boolean>, table: Table<TData>): boolean | 'some' | 'all';
