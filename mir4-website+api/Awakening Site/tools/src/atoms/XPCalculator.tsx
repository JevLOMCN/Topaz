import { type LevelState } from '@/app/xp/page'
import { atom, type Atom, type PrimitiveAtom } from 'jotai'
import { atomWithLocalStorage } from '../utils'

export const XPCalculatorAtom: PrimitiveAtom<XPCalculatorType> &
  Atom<XPCalculatorType> = atomWithLocalStorage('Mir4Tools_XPCalculator', {
  percentages: {
    initial: undefined,
    final: undefined,
  },
  levels: {
    initial: undefined,
    initialPercentage: undefined,
    final: undefined,
  },
  xpPerMinute: undefined,
  manualCalculation: {
    xpPerMinute: undefined,
  },
})

export const customTimerAtom = atom({ minutes: '5', seconds: '0' })

export const XPInvalidInput = atom(false)

interface XPCalculatorType {
  percentages: PercentageState
  levels: LevelState
  xpPerMinute: number | undefined
  manualCalculation: { xpPerMinute: number | undefined }
}

export const XPExtension: PrimitiveAtom<XPExtensionType> &
  Atom<XPExtensionType> = atomWithLocalStorage('Mir4Tools_XPExtension', {
  vigor: 0,
  magicSquare: { tickets: 0, xpPerRun: 0 },
  secretPeak: { tickets: 0, xpPerRun: 0 },
})

interface XPExtensionType {
  vigor: number
  magicSquare: { tickets: number; xpPerRun: number }
  secretPeak: { tickets: number; xpPerRun: number }
}
