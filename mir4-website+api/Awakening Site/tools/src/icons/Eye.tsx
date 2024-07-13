export default function Eye(props: React.SVGProps<SVGSVGElement>) {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width={16}
      height={13}
      fill="none"
      {...props}
    >
      <path
        fill="#fff"
        fillRule="evenodd"
        d="M15.882 6.02C14.184 1.777 10.919-.158 7.654.01 4.428.177 1.384 2.395.094 6.051a.795.795 0 0 0 0 .53c1.29 3.655 4.334 5.873 7.56 6.04 3.264.17 6.53-1.765 8.228-6.01a.795.795 0 0 0 0-.59Zm-7.889 4.268a3.973 3.973 0 1 0 0-7.945 3.973 3.973 0 0 0 0 7.945Z"
        clipRule="evenodd"
      />
      <path
        fill="#fff"
        d="M10.377 6.316a2.384 2.384 0 1 1-4.767 0 2.384 2.384 0 0 1 4.767 0Z"
      />
    </svg>
  )
}
