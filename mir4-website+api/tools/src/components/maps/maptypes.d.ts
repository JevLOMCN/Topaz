type nodeTypes = 'energy' | 'mining' | 'chest' | 'gather' | 'darksteel'

type mapTypes = 'Global Map' | 'Snake Pit Area'
type subMaps = 'snake_pit'
type subMapsWithFloor = 'secret_peak'
type mapPointsObject = Array<{ label: string; pos: [number, number] }>
interface nodeObject {
  type: nodeTypes
  rarity: RarityTypes
  pos: [number, number]
  amount?: number
}
type mapNodesObject = {
  [key in string]: nodeObject
}
