import { secretPeak1f6f } from './SecretPeak/1f-6f'
import { secretPeak7f } from './SecretPeak/7f'
import secretPeak8f from './SecretPeak/8f'
import secretPeak9f from './SecretPeak/9f'
import { SnakePitAreaMapPoints, SnakePitAreaMaps } from './SnakePitArea'

export const GlobalMapPoints: mapPointsObject = [
  // {
  //   label: 'Bicheon Area',
  //   pos: [44, 72],
  // },
  {
    label: 'Snake Pit Area',
    pos: [40, 46],
  },
  // {
  //   label: 'Spiritual Center Area',
  //   pos: [22, 41.5],
  // },
  // {
  //   label: 'Sabuk Area',
  //   pos: [59, 40],
  // },
  // {
  //   label: 'Sabuk Area',
  //   pos: [69.2, 54],
  // },
  // {
  //   label: 'Snowfield Area',
  //   pos: [67, 25.5],
  // },
]

export const MapPointsObject: { [key in mapTypes]: mapPointsObject } = {
  'Global Map': GlobalMapPoints,
  'Snake Pit Area': SnakePitAreaMapPoints,
}

export const MapNodesObject: Record<subMaps, mapNodesObject> &
  Record<subMapsWithFloor, mapNodesObject[]> = {
  ...SnakePitAreaMaps,
  secret_peak: [secretPeak1f6f, secretPeak7f, secretPeak8f, secretPeak9f],
}

export const mapFloors: Partial<{ [key in subMapsWithFloor]: string[] }> = {
  secret_peak: ['1f-6f', '7f', '8f', '9f'],
}
