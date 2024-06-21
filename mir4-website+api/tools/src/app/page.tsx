import Navbar from '@/components/global/Navbar'
import dynamic from 'next/dynamic'

const MainBackground = dynamic(
  async () => await import('@/components/global/MainBackground'),
  { ssr: false }
)
const CraftingMain = dynamic(
  async () => await import('@/components/crafting/CraftingMain'),
  { ssr: false }
)
const CraftingNavExtesion = dynamic(
  async () => await import('@/components/crafting/NavbarExtension'),
  { ssr: false }
)

export default function Home() {
  return (
    <div className="flex min-h-screen w-full bg-black font-main">
      <MainBackground />
      <main className="min-w-screen relative flex h-full w-full flex-col">
        <Navbar>
          <CraftingNavExtesion />
        </Navbar>
        <CraftingMain />
      </main>
    </div>
  )
}
