import { atom, type Atom, type PrimitiveAtom } from 'jotai'
import { atomWithLocalStorage } from '../utils'

export const InnerForceTabAtom = atom<BloodSets>('Muscle Strength Manual')

export type InnerForceObject = {
  [key in BloodNames]: { initial: number; final: number }
}

export const InnerForceBloodsAtom: PrimitiveAtom<InnerForceObject> &
  Atom<InnerForceObject> = atomWithLocalStorage('Mir4Tools_InnerForce', {
  'Sky Palace': { initial: 0, final: 0 },
  'Royal Decree': { initial: 0, final: 0 },
  'Pulsing Sky': { initial: 0, final: 0 },
  'Great Ruler': { initial: 0, final: 0 },
  "Land's End": { initial: 0, final: 0 },
  'Centenary Congregation': { initial: 0, final: 0 },
  'Embroidered Throne': { initial: 0, final: 0 },
  'Golden Jade': { initial: 0, final: 0 },
  'Heart Core': { initial: 0, final: 0 },
  'Virtuous Elevation': { initial: 0, final: 0 },
  Antirelaxation: { initial: 0, final: 0 },
  Springwater: { initial: 0, final: 0 },
  'Pinnacle Star': { initial: 0, final: 0 },
  'Wind Hub': { initial: 0, final: 0 },
  'Great Union': { initial: 0, final: 0 },
  'Earth Valley': { initial: 0, final: 0 },
  Dubhe: { initial: 0, final: 0 },
  'Fertile Scale': { initial: 0, final: 0 },
  Mizar: { initial: 0, final: 0 },
  Alkaid: { initial: 0, final: 0 },
  'Divisive Action': { initial: 0, final: 0 },
  Waterbridge: { initial: 0, final: 0 },
  'United Heaven': { initial: 0, final: 0 },
  Quorum: { initial: 0, final: 0 },
})
