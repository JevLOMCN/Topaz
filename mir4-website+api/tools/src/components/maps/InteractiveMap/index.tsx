import { currentMapPointsAtom, rarityVisibilityAtom } from '@/atoms/Maps'
import { MapNodesObject } from '@/data/Maps'
import ChestNode from '@/icons/ChestNode'
import DarksteelNode from '@/icons/DarksteelNode'
import EnergyNode from '@/icons/EnergyNode'
import GatherNode from '@/icons/GatherNode'
import MiningNode from '@/icons/MiningNode'
import { createNodeGroups, toCamelCase } from '@/utils/index'
import { Transition } from '@headlessui/react'
import { useAtom, useAtomValue } from 'jotai'
import Image from 'next/image'
import { useEffect, useMemo, useState } from 'react'
import { TransformComponent, TransformWrapper } from 'react-zoom-pan-pinch'
import Controls from './Controls'
import MapNode from './MapNode'

const nodeSizeOffset = 10

export default function InteractiveMap({
  mapsStack,
  floor,
}: {
  mapsStack: string[]
  floor?: number
}) {
  const [currentMapPoints, setCurrentMapPoints] = useAtom(currentMapPointsAtom)
  const rarityVisiblity = useAtomValue(rarityVisibilityAtom)
  const [zoom, setZoom] = useState(1)
  const [isDragging, setIsDragging] = useState(false)

  const nodeScale = 1.5 * Math.exp(-zoom / 5)
  const lastMap = toCamelCase(mapsStack.at(-1))

  useEffect(
    () =>
      Array.isArray(MapNodesObject[lastMap as subMaps])
        ? setCurrentMapPoints(
            MapNodesObject[lastMap as subMapsWithFloor][floor as number]
          )
        : setCurrentMapPoints(MapNodesObject[lastMap as subMaps]),
    [JSON.stringify(mapsStack), floor]
  )

  const displayCurrentPoints = useMemo(
    () =>
      Object.entries(
        zoom < 2 ? createNodeGroups(currentMapPoints) : currentMapPoints
      ),
    [zoom, JSON.stringify(currentMapPoints)]
  )

  const handleNodeDeletion = (id: string) => {
    setCurrentMapPoints((prev) => {
      const newObj = structuredClone(prev)
      delete newObj?.[id]

      return newObj
    })
  }

  const handleNodeCreation = (
    e: React.MouseEvent<HTMLDivElement, MouseEvent>
  ) => {
    e.preventDefault()
    if (e.currentTarget !== e.target) return

    const x =
      (e.nativeEvent.offsetX - nodeSizeOffset) / e.currentTarget.clientWidth
    const y =
      (e.nativeEvent.offsetY - nodeSizeOffset) / e.currentTarget.clientHeight

    const posX = (x * 100).toFixed(2)
    const posY = (y * 100).toFixed(2)
    const id = Math.random().toString(16).slice(2)

    setCurrentMapPoints((prev) => ({
      ...prev,
      [id]: {
        pos: [Number(posX), Number(posY)],
        rarity: 'Rare',
        type: 'energy',
      },
    }))
  }

  return (
    <TransformWrapper
      wheel={{ smoothStep: 0.005 }}
      smooth
      onZoom={(e) => setZoom(e.state.scale)}
      onPanningStart={() => setIsDragging(true)}
      onPanningStop={() => setIsDragging(false)}
    >
      {({ zoomIn, zoomOut, resetTransform }) => (
        <>
          <Controls
            centerView={() => resetTransform()}
            zoomIn={() => zoomIn()}
            zoomOut={() => zoomOut()}
          />
          <TransformComponent wrapperClass="rounded-lg">
            <div
              onContextMenu={handleNodeCreation}
              className="flex items-center justify-center"
              style={{
                cursor: isDragging ? 'grabbing' : 'grab',
              }}
            >
              <Image
                src={`/maps/${lastMap}.webp`}
                alt=""
                width={600}
                height={600}
                className={'pointer-events-none select-none object-contain'}
                sizes="100%"
              />
              {displayCurrentPoints.map(([id, obj]) => {
                const { pos, rarity, type } = obj
                return (
                  <Transition
                    key={id}
                    show={rarityVisiblity[type][rarity]}
                    enter="transition-transform duration-200"
                    enterFrom="opacity-0"
                    enterTo={'opacity-100'}
                    leave="transition-opacity duration-300"
                    leaveFrom={'opacity-100'}
                    leaveTo="opacity-0"
                  >
                    <MapNode
                      id={id}
                      pos={pos}
                      isVisible={rarityVisiblity[type][rarity]}
                      handleNodeDeletion={() => handleNodeDeletion(id)}
                      nodeScale={nodeScale}
                      rarity={rarity}
                      type={type}
                      amount={obj?.amount}
                    />
                  </Transition>
                )
              })}
            </div>
          </TransformComponent>
        </>
      )}
    </TransformWrapper>
  )
}

export const mapNodeTypes = [
  'energy',
  'mining',
  'darksteel',
  'chest',
  'gather',
] as const
export const nodeTypeToIcon: {
  [id in nodeTypes]: (props: React.SVGProps<SVGSVGElement>) => JSX.Element
} = {
  chest: ChestNode,
  energy: EnergyNode,
  gather: GatherNode,
  mining: MiningNode,
  darksteel: DarksteelNode,
}
