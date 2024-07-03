export default function Hamburguer(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox='0 0 26 20' {...props}>
    <rect width={25.129} height={3.065} y={0.5} fill="#EAEAEA" rx={1.532} />
    <rect width={25.129} height={3.065} y={8.468} fill="#EAEAEA" rx={1.532} />
    <rect width={25.129} height={3.065} y={16.435} fill="#EAEAEA" rx={1.532} />
  </svg>
  )
}
