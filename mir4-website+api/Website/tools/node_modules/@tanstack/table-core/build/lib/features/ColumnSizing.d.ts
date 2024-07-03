import { TableFeature } from '../core/table';
import { OnChangeFn, Updater } from '../types';
import { ColumnPinningPosition } from './Pinning';
export interface ColumnSizingTableState {
    columnSizing: ColumnSizingState;
    columnSizingInfo: ColumnSizingInfoState;
}
export type ColumnSizingState = Record<string, number>;
export interface ColumnSizingInfoState {
    startOffset: null | number;
    startSize: null | number;
    deltaOffset: null | number;
    deltaPercentage: null | number;
    isResizingColumn: false | string;
    columnSizingStart: [string, number][];
}
export type ColumnResizeMode = 'onChange' | 'onEnd';
export interface ColumnSizingOptions {
    enableColumnResizing?: boolean;
    columnResizeMode?: ColumnResizeMode;
    onColumnSizingChange?: OnChangeFn<ColumnSizingState>;
    onColumnSizingInfoChange?: OnChangeFn<ColumnSizingInfoState>;
}
export interface ColumnSizingDefaultOptions {
    columnResizeMode: ColumnResizeMode;
    onColumnSizingChange: OnChangeFn<ColumnSizingState>;
    onColumnSizingInfoChange: OnChangeFn<ColumnSizingInfoState>;
}
export interface ColumnSizingInstance {
    setColumnSizing: (updater: Updater<ColumnSizingState>) => void;
    setColumnSizingInfo: (updater: Updater<ColumnSizingInfoState>) => void;
    resetColumnSizing: (defaultState?: boolean) => void;
    resetHeaderSizeInfo: (defaultState?: boolean) => void;
    getTotalSize: () => number;
    getLeftTotalSize: () => number;
    getCenterTotalSize: () => number;
    getRightTotalSize: () => number;
}
export interface ColumnSizingColumnDef {
    enableResizing?: boolean;
    size?: number;
    minSize?: number;
    maxSize?: number;
}
export interface ColumnSizingColumn {
    getSize: () => number;
    getStart: (position?: ColumnPinningPosition) => number;
    getCanResize: () => boolean;
    getIsResizing: () => boolean;
    resetSize: () => void;
}
export interface ColumnSizingHeader {
    getSize: () => number;
    getStart: (position?: ColumnPinningPosition) => number;
    getResizeHandler: () => (event: unknown) => void;
}
export declare const defaultColumnSizing: {
    size: number;
    minSize: number;
    maxSize: number;
};
export declare const ColumnSizing: TableFeature;
export declare function passiveEventSupported(): boolean;
