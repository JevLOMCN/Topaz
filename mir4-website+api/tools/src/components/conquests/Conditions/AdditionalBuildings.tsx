'use client'

import { ConquestsAtom } from '@/atoms/Conquests'
import ConquestTowersData from '@/data/ConquestTowerData'
import { toCamelCase } from '@/utils/index'
import { useAtom } from 'jotai'
import { useMemo } from 'react'
import { useTranslation } from '../../../../public/locales/client'
import ConditionCard from './ConditionCard'

export default function AditionalBuildingsConditions() {
  const [{ tower, stage }, setConquests] = useAtom(ConquestsAtom)
  const { t } = useTranslation()

  const currentTower = ConquestTowersData[tower].Steps[stage]
  const hasBuildingInConditions =
    Object.keys(currentTower?.Building ?? {}).length > 0
  const subBuildingConditions = useMemo(
    () =>
      retrieveBuildingsRecursive({
        building: currentTower?.Building,
        baseBuildingConditions: currentTower?.Building ?? {},
        towerName: tower,
      }),
    [tower, stage]
  )

  if (!hasBuildingInConditions) return <></>

  const objectIsEmpty =
    !subBuildingConditions || Object.keys(subBuildingConditions).length <= 0

  if (objectIsEmpty) return <></>

  return (
    <div className="mt-2 flex flex-col gap-4">
      <h2 className="text-center text-xl font-bold text-white">
        Additional required buildings
      </h2>

      <div className="custom-scroll relative mx-auto flex flex-row flex-wrap items-start justify-start gap-4 overflow-auto px-2 py-3 sm:flex-nowrap lg:max-w-7xl lg:px-8 lg:py-6">
        {Object.entries(subBuildingConditions).map(([buildingName, level]) => (
          <ConditionCard
            key={buildingName}
            image={`/conquests/previews/${toCamelCase(buildingName)}.png`}
            name={t(buildingName)}
            level={level}
            onConditionSelection={() =>
              setConquests({
                tower: buildingName as ConquestTowers,
                stage: level ?? stage,
              })
            }
          />
        ))}
      </div>
    </div>
  )
}

function retrieveBuildingsRecursive({
  building,
  baseBuildingConditions,
  towerName,
  result = {},
  isBaseCase = true,
}: {
  building?: Partial<{ [key in ConquestTowers]: number }>
  baseBuildingConditions: Partial<{ [key in ConquestTowers]: number }>
  towerName: ConquestTowers
  result?: Partial<{ [key in ConquestTowers]: number }>
  isBaseCase?: boolean
}) {
  for (const currentBuilding of Object.entries(building ?? {})) {
    const [buildName, level] = currentBuilding as [ConquestTowers, number]

    if (
      ((buildName in result || buildName in baseBuildingConditions) &&
        !isBaseCase) ||
      buildName === towerName
    ) {
      continue
    }

    if (!(buildName in baseBuildingConditions)) {
      result[buildName] = level
    }

    retrieveBuildingsRecursive({
      building: ConquestTowersData?.[buildName]?.Steps?.[level - 1]?.Building,
      baseBuildingConditions,
      towerName,
      result,
      isBaseCase: false,
    })
  }

  return result
}
